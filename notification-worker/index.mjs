import http from 'node:http';
import process from 'node:process';
import axios from 'axios';
import nodemailer from 'nodemailer';
import pg from 'pg';

const {
  DB_HOST = 'onlifin-database',
  DB_PORT = '5432',
  DB_NAME = 'onlifin',
  DB_USER = 'onlifin',
  DB_PASSWORD = 'secretpassword',
  NOTIFICATION_WORKER_INTERVAL_MS = '15000',
  NOTIFICATION_GENERATOR_INTERVAL_MS = '300000',
  NOTIFICATION_COMMAND_POLL_INTERVAL_MS = '5000',
  NOTIFICATION_WORKER_BATCH_SIZE = '10',
  SMTP_HOST,
  SMTP_PORT = '587',
  SMTP_SECURE = 'false',
  SMTP_USER,
  SMTP_PASS,
  SMTP_FROM_NAME = 'OnliFin',
  SMTP_FROM_ADDRESS,
  WHATSAPP_API_BASE_URL,
  WHATSAPP_API_TOKEN,
  WHATSAPP_PROVIDER = 'generic',
  WORKER_HEALTH_PORT = '8091'
} = process.env;

const pool = new pg.Pool({
  host: DB_HOST,
  port: Number(DB_PORT),
  database: DB_NAME,
  user: DB_USER,
  password: DB_PASSWORD
});

const transporter = SMTP_HOST
  ? nodemailer.createTransport({
      host: SMTP_HOST,
      port: Number(SMTP_PORT),
      secure: SMTP_SECURE === 'true',
      auth: SMTP_USER && SMTP_PASS ? { user: SMTP_USER, pass: SMTP_PASS } : undefined
    })
  : null;

function getMissingSmtpEnvKeys() {
  const missing = [];

  if (!SMTP_HOST) {
    missing.push('SMTP_HOST');
  }

  if (!SMTP_FROM_ADDRESS && !process.env.EMAIL_FROM_ADDRESS) {
    missing.push('SMTP_FROM_ADDRESS');
  }

  if (SMTP_USER && !SMTP_PASS) {
    missing.push('SMTP_PASS');
  }

  if (!SMTP_USER && SMTP_PASS) {
    missing.push('SMTP_USER');
  }

  return missing;
}

function getMissingWhatsappEnvKeys() {
  const missing = [];

  if (!WHATSAPP_API_BASE_URL) {
    missing.push('WHATSAPP_API_BASE_URL');
  }

  return missing;
}

let isRunning = false;
let lastRunAt = null;
let lastError = null;
let isGenerating = false;
let lastGenerationRunAt = null;
let lastGenerationError = null;
let isProcessingCommands = false;
let lastCommandRunAt = null;
let lastCommandError = null;
let commandsTableMissingLogged = false;

const DEFAULT_SETTINGS = {
  is_active: true,
  toast_enabled: true,
  database_enabled: true,
  email_enabled: false,
  whatsapp_enabled: false,
  days_before_due_default: 3,
  days_before_overdue_default: 1,
  quiet_hours_start_default: '22:00:00',
  quiet_hours_end_default: '08:00:00',
  weekend_notifications_default: true,
  alert_due_soon_default: true,
  alert_overdue_default: true,
  alert_received_default: true,
  system_critical_default: true
};

const DEFAULT_TEMPLATE_MAP = {
  bill_due_soon: {
    toast: {
      title_template: 'Conta vencendo em breve',
      subject_template: null,
      body_template: '{{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'
    },
    email: {
      title_template: 'Conta vencendo em breve',
      subject_template: 'OnliFin: {{description}} vence em breve',
      body_template: '{{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}. Vencimento: {{due_date}}.'
    },
    whatsapp: {
      title_template: 'Conta vencendo em breve',
      subject_template: null,
      body_template: 'OnliFin: {{description}} vence em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'
    }
  },
  bill_overdue: {
    toast: {
      title_template: 'Conta vencida',
      subject_template: null,
      body_template: '{{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}.'
    },
    email: {
      title_template: 'Conta vencida',
      subject_template: 'OnliFin: {{description}} está vencida',
      body_template: '{{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}. Vencimento: {{due_date}}.'
    },
    whatsapp: {
      title_template: 'Conta vencida',
      subject_template: null,
      body_template: 'OnliFin: {{description}} está vencida há {{days_overdue}} dia(s) no valor de R$ {{amount}}.'
    }
  },
  bill_to_receive_due_soon: {
    toast: {
      title_template: 'Recebimento em breve',
      subject_template: null,
      body_template: '{{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'
    },
    email: {
      title_template: 'Recebimento em breve',
      subject_template: 'OnliFin: recebimento próximo',
      body_template: '{{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'
    },
    whatsapp: {
      title_template: 'Recebimento em breve',
      subject_template: null,
      body_template: 'OnliFin: {{description}} será recebido em {{days_until_due}} dia(s) no valor de R$ {{amount}}.'
    }
  }
};

function getBackoffMinutes(attempts) {
  return Math.min(60, Math.max(1, 2 ** Math.max(0, attempts - 1)));
}

function normalizeMoney(value) {
  return Number(value || 0).toFixed(2).replace('.', ',');
}

function toDateOnlyString(value) {
  if (value instanceof Date) {
    return value.toISOString().slice(0, 10);
  }

  if (typeof value === 'string') {
    return value.slice(0, 10);
  }

  return String(value).slice(0, 10);
}

function renderTemplate(template, payload) {
  if (!template) return '';

  return template.replace(/\{\{\s*([\w.]+)\s*\}\}/g, (_, key) => {
    const value = payload[key];
    if (value === undefined || value === null) return '';
    return String(value);
  });
}

function getDefaultTemplate(eventKey, channel) {
  return DEFAULT_TEMPLATE_MAP[eventKey]?.[channel] || null;
}

function buildEffectivePreferences(row, settings) {
  return {
    days_before_due: row.days_before_due ?? settings.days_before_due_default,
    days_before_overdue: row.days_before_overdue ?? settings.days_before_overdue_default,
    alert_due_soon: row.alert_due_soon ?? settings.alert_due_soon_default,
    alert_overdue: row.alert_overdue ?? settings.alert_overdue_default,
    alert_received: row.alert_received ?? settings.alert_received_default,
    toast_notifications: row.toast_notifications ?? true,
    database_notifications: row.database_notifications ?? true,
    email_notifications: row.email_notifications ?? false,
    whatsapp_notifications: row.whatsapp_notifications ?? false,
    quiet_hours_start: row.quiet_hours_start ?? settings.quiet_hours_start_default,
    quiet_hours_end: row.quiet_hours_end ?? settings.quiet_hours_end_default,
    weekend_notifications: row.weekend_notifications ?? settings.weekend_notifications_default
  };
}

function isWeekend() {
  const day = new Date().getDay();
  return day === 0 || day === 6;
}

function isQuietHours(preferences) {
  const now = new Date();
  const currentTime = now.toTimeString().slice(0, 5);
  const [startHour, startMinute] = preferences.quiet_hours_start.split(':').map(Number);
  const [endHour, endMinute] = preferences.quiet_hours_end.split(':').map(Number);
  const [currentHour, currentMinute] = currentTime.split(':').map(Number);

  const currentTotal = currentHour * 60 + currentMinute;
  const startTotal = startHour * 60 + startMinute;
  const endTotal = endHour * 60 + endMinute;

  if (startTotal > endTotal) {
    return currentTotal >= startTotal || currentTotal <= endTotal;
  }

  return currentTotal >= startTotal && currentTotal <= endTotal;
}

function canDeliverExternal(preferences) {
  if (isQuietHours(preferences)) {
    return false;
  }

  if (!preferences.weekend_notifications && isWeekend()) {
    return false;
  }

  return true;
}

function getActionUrl(kind, companyId) {
  return companyId ? `/pj/${companyId}/${kind}` : `/pf/${kind}`;
}

function getWindowStart(frequency) {
  if (frequency === 'once') {
    return null;
  }

  if (frequency === 'weekly') {
    return new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString();
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return today.toISOString();
}

async function loadGlobalSettings(client) {
  const { rows } = await client.query(
    `
    SELECT *
    FROM public.notification_settings
    WHERE settings_key = 'global'
    LIMIT 1
    `
  );

  return rows[0] || DEFAULT_SETTINGS;
}

async function loadTemplates(client) {
  const { rows } = await client.query(
    `
    SELECT *
    FROM public.notification_templates
    WHERE is_active = true
    `
  );

  return rows;
}

function resolveTemplate(templates, eventKey, channel) {
  return templates.find((item) => item.event_key === eventKey && item.channel === channel) || getDefaultTemplate(eventKey, channel);
}

async function hasExistingNotification(client, {
  userId,
  eventKey,
  relationColumn,
  relationId,
  windowStart
}) {
  const params = [userId, eventKey, relationId];
  let query = `
    SELECT 1
    FROM public.notifications
    WHERE user_id = $1
      AND event_key = $2
      AND ${relationColumn} = $3
  `;

  if (windowStart) {
    params.push(windowStart);
    query += ` AND created_at >= $4`;
  }

  query += ' LIMIT 1';
  const { rows } = await client.query(query, params);
  return rows.length > 0;
}

async function createDatabaseNotification(client, data) {
  const { rows } = await client.query(
    `
    INSERT INTO public.notifications (
      user_id,
      title,
      message,
      type,
      severity,
      is_read,
      related_bill_id,
      related_bill_to_receive_id,
      related_transaction_id,
      related_forecast_id,
      person_id,
      event_key,
      action_url,
      metadata
    )
    VALUES ($1,$2,$3,$4,$5,false,$6,$7,NULL,NULL,$8,$9,$10,$11)
    RETURNING id
    `,
    [
      data.user_id,
      data.title,
      data.message,
      data.type,
      data.severity,
      data.related_bill_id || null,
      data.related_bill_to_receive_id || null,
      data.person_id || null,
      data.event_key,
      data.action_url || null,
      data.metadata || {}
    ]
  );

  return rows[0]?.id || null;
}

async function enqueueExternalDelivery(client, {
  notificationId,
  userId,
  channel,
  destination,
  template,
  payload
}) {
  const subject = renderTemplate(template?.subject_template, payload) || null;
  const content = renderTemplate(template?.body_template, payload);

  if (!content.trim()) {
    return;
  }

  await client.query(
    `
    INSERT INTO public.notification_delivery_queue (
      notification_id,
      user_id,
      channel,
      destination,
      subject,
      content,
      template_id,
      payload,
      status,
      attempts,
      max_attempts,
      next_attempt_at,
      provider_response
    )
    VALUES ($1,$2,$3,$4,$5,$6,$7,$8,'pending',0,5,now(),$9)
    `,
    [
      notificationId,
      userId,
      channel,
      destination,
      subject,
      content,
      template?.id?.startsWith?.('default-') ? null : (template?.id || null),
      payload,
      {}
    ]
  );
}

async function createScheduledNotification(client, {
  settings,
  templates,
  preferences,
  eventKey,
  notificationType,
  severity,
  relationColumn,
  relationId,
  userId,
  personId,
  companyId,
  email,
  whatsapp,
  payload,
  kind
}) {
  const toastTemplate = resolveTemplate(templates, eventKey, 'toast');
  const title = renderTemplate(toastTemplate?.title_template, payload) || payload.title || 'Notificação';
  const message = renderTemplate(toastTemplate?.body_template, payload) || payload.message || '';
  const metadata = { ...payload };
  delete metadata.title;
  delete metadata.message;

  let notificationId = null;

  if (settings.database_enabled && preferences.database_notifications) {
    notificationId = await createDatabaseNotification(client, {
      user_id: userId,
      title,
      message,
      type: notificationType,
      severity,
      related_bill_id: relationColumn === 'related_bill_id' ? relationId : null,
      related_bill_to_receive_id: relationColumn === 'related_bill_to_receive_id' ? relationId : null,
      person_id: personId,
      event_key: eventKey,
      action_url: getActionUrl(kind, companyId),
      metadata
    });
  }

  if (!canDeliverExternal(preferences)) {
    return;
  }

  if (settings.email_enabled && preferences.email_notifications && email) {
    await enqueueExternalDelivery(client, {
      notificationId,
      userId,
      channel: 'email',
      destination: email,
      template: resolveTemplate(templates, eventKey, 'email'),
      payload
    });
  }

  if (settings.whatsapp_enabled && preferences.whatsapp_notifications && whatsapp) {
    await enqueueExternalDelivery(client, {
      notificationId,
      userId,
      channel: 'whatsapp',
      destination: whatsapp,
      template: resolveTemplate(templates, eventKey, 'whatsapp'),
      payload
    });
  }
}

async function generateBillToPayNotifications(client, settings, templates) {
  const { rows } = await client.query(
    `
    SELECT
      bill.id,
      bill.user_id,
      bill.company_id,
      bill.person_id,
      bill.description,
      bill.amount,
      bill.due_date,
      bill.notification_mode,
      bill.notification_frequency,
      bill.custom_days_before,
      profile.email,
      profile.whatsapp,
      profile.status AS profile_status,
      pref.days_before_due,
      pref.days_before_overdue,
      pref.alert_due_soon,
      pref.alert_overdue,
      pref.toast_notifications,
      pref.database_notifications,
      pref.email_notifications,
      pref.whatsapp_notifications,
      pref.quiet_hours_start,
      pref.quiet_hours_end,
      pref.weekend_notifications
    FROM public.bills_to_pay AS bill
    INNER JOIN public.profiles AS profile ON profile.id = bill.user_id
    LEFT JOIN public.alert_preferences AS pref ON pref.user_id = bill.user_id
    WHERE bill.status = 'pending'
      AND bill.due_date IS NOT NULL
      AND COALESCE(bill.notification_mode, 'default') <> 'disabled'
      AND COALESCE(profile.status, 'active') = 'active'
    `
  );

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (const row of rows) {
    const preferences = buildEffectivePreferences(row, settings);
    const dueDateString = toDateOnlyString(row.due_date);
    const dueDate = new Date(`${dueDateString}T00:00:00Z`);
    const diffMs = dueDate.getTime() - today.getTime();
    const daysUntilDue = Math.round(diffMs / (1000 * 60 * 60 * 24));
    const daysOverdue = Math.round((today.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24));
    const daysBeforeDue = row.notification_mode === 'custom' && row.custom_days_before !== null
      ? row.custom_days_before
      : preferences.days_before_due;
    const frequency = row.notification_frequency || 'standard';

    if (preferences.alert_due_soon && daysUntilDue >= 0 && daysUntilDue <= daysBeforeDue) {
      const windowStart = getWindowStart(frequency);
      const exists = await hasExistingNotification(client, {
        userId: row.user_id,
        eventKey: 'bill_due_soon',
        relationColumn: 'related_bill_id',
        relationId: row.id,
        windowStart
      });

      if (!exists) {
        await createScheduledNotification(client, {
          settings,
          templates,
          preferences,
          eventKey: 'bill_due_soon',
          notificationType: 'alert',
          severity: daysUntilDue <= 1 ? 'high' : daysUntilDue <= 3 ? 'medium' : 'low',
          relationColumn: 'related_bill_id',
          relationId: row.id,
          userId: row.user_id,
          personId: row.person_id,
          companyId: row.company_id,
          email: row.email,
          whatsapp: row.whatsapp,
          payload: {
            title: 'Conta vencendo em breve',
            message: `${row.description} vence em ${daysUntilDue} dia(s) no valor de R$ ${normalizeMoney(row.amount)}.`,
            description: row.description,
            amount: normalizeMoney(row.amount),
            due_date: dueDateString,
            days_until_due: daysUntilDue,
            bill_id: row.id
          },
          kind: 'bills-to-pay'
        });
      }
    }

    if (preferences.alert_overdue && daysOverdue >= preferences.days_before_overdue) {
      const windowStart = getWindowStart(frequency);
      const exists = await hasExistingNotification(client, {
        userId: row.user_id,
        eventKey: 'bill_overdue',
        relationColumn: 'related_bill_id',
        relationId: row.id,
        windowStart
      });

      if (!exists) {
        await createScheduledNotification(client, {
          settings,
          templates,
          preferences,
          eventKey: 'bill_overdue',
          notificationType: 'warning',
          severity: daysOverdue >= 7 ? 'high' : 'medium',
          relationColumn: 'related_bill_id',
          relationId: row.id,
          userId: row.user_id,
          personId: row.person_id,
          companyId: row.company_id,
          email: row.email,
          whatsapp: row.whatsapp,
          payload: {
            title: 'Conta vencida',
            message: `${row.description} está vencida há ${daysOverdue} dia(s) no valor de R$ ${normalizeMoney(row.amount)}.`,
            description: row.description,
            amount: normalizeMoney(row.amount),
            due_date: dueDateString,
            days_overdue: daysOverdue,
            bill_id: row.id
          },
          kind: 'bills-to-pay'
        });
      }
    }
  }
}

async function generateBillToReceiveNotifications(client, settings, templates) {
  const { rows } = await client.query(
    `
    SELECT
      bill.id,
      bill.user_id,
      bill.company_id,
      bill.person_id,
      bill.description,
      bill.amount,
      bill.due_date,
      bill.notification_mode,
      bill.notification_frequency,
      bill.custom_days_before,
      profile.email,
      profile.whatsapp,
      profile.status AS profile_status,
      pref.days_before_due,
      pref.alert_received,
      pref.toast_notifications,
      pref.database_notifications,
      pref.email_notifications,
      pref.whatsapp_notifications,
      pref.quiet_hours_start,
      pref.quiet_hours_end,
      pref.weekend_notifications
    FROM public.bills_to_receive AS bill
    INNER JOIN public.profiles AS profile ON profile.id = bill.user_id
    LEFT JOIN public.alert_preferences AS pref ON pref.user_id = bill.user_id
    WHERE bill.status = 'pending'
      AND bill.due_date IS NOT NULL
      AND COALESCE(bill.notification_mode, 'default') <> 'disabled'
      AND COALESCE(profile.status, 'active') = 'active'
    `
  );

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (const row of rows) {
    const preferences = buildEffectivePreferences(row, settings);
    const dueDateString = toDateOnlyString(row.due_date);
    const dueDate = new Date(`${dueDateString}T00:00:00Z`);
    const diffMs = dueDate.getTime() - today.getTime();
    const daysUntilDue = Math.round(diffMs / (1000 * 60 * 60 * 24));
    const daysBeforeDue = row.notification_mode === 'custom' && row.custom_days_before !== null
      ? row.custom_days_before
      : preferences.days_before_due;
    const frequency = row.notification_frequency || 'standard';

    if (!preferences.alert_received || daysUntilDue < 0 || daysUntilDue > daysBeforeDue) {
      continue;
    }

    const windowStart = getWindowStart(frequency);
    const exists = await hasExistingNotification(client, {
      userId: row.user_id,
      eventKey: 'bill_to_receive_due_soon',
      relationColumn: 'related_bill_to_receive_id',
      relationId: row.id,
      windowStart
    });

    if (exists) {
      continue;
    }

    await createScheduledNotification(client, {
      settings,
      templates,
      preferences,
      eventKey: 'bill_to_receive_due_soon',
      notificationType: 'info',
      severity: 'low',
      relationColumn: 'related_bill_to_receive_id',
      relationId: row.id,
      userId: row.user_id,
      personId: row.person_id,
      companyId: row.company_id,
      email: row.email,
      whatsapp: row.whatsapp,
      payload: {
        title: 'Recebimento em breve',
        message: `${row.description} será recebido em ${daysUntilDue} dia(s) no valor de R$ ${normalizeMoney(row.amount)}.`,
        description: row.description,
        amount: normalizeMoney(row.amount),
        due_date: dueDateString,
        days_until_due: daysUntilDue,
        bill_id: row.id
      },
      kind: 'bills-to-receive'
    });
  }
}

async function generateScheduledNotifications() {
  if (isGenerating) {
    return { skipped: true, reason: 'already_running' };
  }
  isGenerating = true;

  const client = await pool.connect();
  try {
    const settings = await loadGlobalSettings(client);
    if (!settings.is_active) {
      lastGenerationRunAt = new Date().toISOString();
      lastGenerationError = null;
      return { skipped: true, reason: 'notifications_disabled' };
    }

    const templates = await loadTemplates(client);
    await generateBillToPayNotifications(client, settings, templates);
    await generateBillToReceiveNotifications(client, settings, templates);
    lastGenerationRunAt = new Date().toISOString();
    lastGenerationError = null;
    return {
      generatedAt: lastGenerationRunAt,
      success: true
    };
  } catch (error) {
    lastGenerationError = error instanceof Error ? error.message : 'Erro desconhecido';
    console.error('[notification-worker] Erro ao gerar notificacoes programadas:', error);
    throw error;
  } finally {
    client.release();
    isGenerating = false;
  }
}

async function claimQueueItems(limit) {
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    const { rows } = await client.query(
      `
      WITH next_items AS (
        SELECT id
        FROM public.notification_delivery_queue
        WHERE status IN ('pending', 'retrying')
          AND next_attempt_at <= now()
        ORDER BY created_at ASC
        FOR UPDATE SKIP LOCKED
        LIMIT $1
      )
      UPDATE public.notification_delivery_queue AS queue
      SET status = 'processing',
          attempts = queue.attempts + 1,
          updated_at = now()
      FROM next_items
      WHERE queue.id = next_items.id
      RETURNING queue.*
      `,
      [limit]
    );
    await client.query('COMMIT');
    return rows;
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
}

async function logDelivery(client, item, status, provider, providerResponse, errorMessage = null) {
  await client.query(
    `
    INSERT INTO public.notification_deliveries (
      queue_id,
      notification_id,
      user_id,
      channel,
      destination,
      provider,
      status,
      error_message,
      provider_response
    )
    VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)
    `,
    [
      item.id,
      item.notification_id,
      item.user_id,
      item.channel,
      item.destination,
      provider,
      status,
      errorMessage,
      providerResponse || {}
    ]
  );
}

async function markQueueSuccess(client, item, providerResponse) {
  await client.query(
    `
    UPDATE public.notification_delivery_queue
    SET status = 'sent',
        sent_at = now(),
        last_error = NULL,
        provider_response = $2,
        updated_at = now()
    WHERE id = $1
    `,
    [item.id, providerResponse || {}]
  );
}

async function markQueueFailure(client, item, errorMessage) {
  const shouldRetry = item.attempts < item.max_attempts;
  const nextAttemptAt = shouldRetry
    ? new Date(Date.now() + getBackoffMinutes(item.attempts) * 60 * 1000).toISOString()
    : null;

  await client.query(
    `
    UPDATE public.notification_delivery_queue
    SET status = $2,
        next_attempt_at = COALESCE($3::timestamptz, next_attempt_at),
        last_error = $4,
        updated_at = now()
    WHERE id = $1
    `,
    [item.id, shouldRetry ? 'retrying' : 'failed', nextAttemptAt, errorMessage]
  );
}

async function sendEmail(item) {
  if (!transporter) {
    throw new Error('SMTP nao configurado no worker');
  }

  const fromAddress = SMTP_FROM_ADDRESS || process.env.EMAIL_FROM_ADDRESS;
  if (!fromAddress) {
    throw new Error('Endereco remetente SMTP nao configurado');
  }

  const result = await transporter.sendMail({
    from: `"${SMTP_FROM_NAME}" <${fromAddress}>`,
    to: item.destination,
    subject: item.subject || 'OnliFin',
    text: item.content,
    html: `<div style="font-family:Arial,sans-serif;line-height:1.5;white-space:pre-wrap">${item.content}</div>`
  });

  return {
    messageId: result.messageId,
    accepted: result.accepted,
    rejected: result.rejected
  };
}

async function sendWhatsapp(item) {
  if (!WHATSAPP_API_BASE_URL) {
    throw new Error('Base URL do WhatsApp nao configurada');
  }

  const response = await axios.post(
    `${WHATSAPP_API_BASE_URL.replace(/\/$/, '')}/messages`,
    {
      to: item.destination,
      message: item.content,
      channel: 'whatsapp'
    },
    {
      headers: {
        'Content-Type': 'application/json',
        ...(WHATSAPP_API_TOKEN ? { Authorization: `Bearer ${WHATSAPP_API_TOKEN}` } : {})
      },
      timeout: 15000
    }
  );

  return {
    provider: WHATSAPP_PROVIDER,
    status: response.status,
    data: response.data
  };
}

async function processItem(item) {
  const client = await pool.connect();
  try {
    let provider = item.channel === 'email' ? 'smtp' : WHATSAPP_PROVIDER;
    let providerResponse = {};

    if (item.channel === 'email') {
      providerResponse = await sendEmail(item);
    } else if (item.channel === 'whatsapp') {
      providerResponse = await sendWhatsapp(item);
    } else {
      throw new Error(`Canal nao suportado: ${item.channel}`);
    }

    await client.query('BEGIN');
    await markQueueSuccess(client, item, providerResponse);
    await logDelivery(client, item, 'sent', provider, providerResponse, null);
    await client.query('COMMIT');
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Falha desconhecida na entrega';
    try {
      await client.query('BEGIN');
      await markQueueFailure(client, item, message);
      await logDelivery(client, item, 'failed', item.channel === 'email' ? 'smtp' : WHATSAPP_PROVIDER, {}, message);
      await client.query('COMMIT');
    } catch (dbError) {
      await client.query('ROLLBACK');
      throw dbError;
    }
  } finally {
    client.release();
  }
}

async function processQueue() {
  if (isRunning) {
    return { skipped: true, reason: 'already_running', processedCount: 0 };
  }
  isRunning = true;
  try {
    const items = await claimQueueItems(Number(NOTIFICATION_WORKER_BATCH_SIZE));
    for (const item of items) {
      await processItem(item);
    }
    lastRunAt = new Date().toISOString();
    lastError = null;
    return {
      processedCount: items.length,
      processedAt: lastRunAt
    };
  } catch (error) {
    lastError = error instanceof Error ? error.message : 'Erro desconhecido';
    console.error('[notification-worker] Erro ao processar fila:', error);
    throw error;
  } finally {
    isRunning = false;
  }
}

async function claimNextWorkerCommand() {
  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    const { rows } = await client.query(
      `
      WITH next_command AS (
        SELECT id
        FROM public.notification_worker_commands
        WHERE status = 'pending'
        ORDER BY requested_at ASC
        FOR UPDATE SKIP LOCKED
        LIMIT 1
      )
      UPDATE public.notification_worker_commands AS command
      SET status = 'processing',
          started_at = now(),
          error_message = NULL,
          updated_at = now()
      FROM next_command
      WHERE command.id = next_command.id
      RETURNING command.*
      `
    );
    await client.query('COMMIT');
    return rows[0] || null;
  } catch (error) {
    await client.query('ROLLBACK');

    if (error?.code === '42P01') {
      if (!commandsTableMissingLogged) {
        console.warn('[notification-worker] Tabela notification_worker_commands ainda nao existe; comandos manuais desativados ate a proxima reinicializacao');
        commandsTableMissingLogged = true;
      }
      return null;
    }

    throw error;
  } finally {
    client.release();
  }
}

async function completeWorkerCommand(id, result = {}) {
  await pool.query(
    `
    UPDATE public.notification_worker_commands
    SET status = 'completed',
        result = $2,
        error_message = NULL,
        completed_at = now(),
        updated_at = now()
    WHERE id = $1
    `,
    [id, result]
  );
}

async function failWorkerCommand(id, message) {
  await pool.query(
    `
    UPDATE public.notification_worker_commands
    SET status = 'failed',
        error_message = $2,
        completed_at = now(),
        updated_at = now()
    WHERE id = $1
    `,
    [id, message]
  );
}

async function executeWorkerCommand(command) {
  switch (command.command) {
    case 'process_queue':
      return processQueue();
    case 'generate_notifications':
      return generateScheduledNotifications();
    default:
      throw new Error(`Comando de worker nao suportado: ${command.command}`);
  }
}

async function processPendingWorkerCommands() {
  if (isProcessingCommands) return;
  isProcessingCommands = true;

  try {
    while (true) {
      const command = await claimNextWorkerCommand();
      if (!command) {
        break;
      }

      try {
        const result = await executeWorkerCommand(command);
        await completeWorkerCommand(command.id, result || {});
        lastCommandRunAt = new Date().toISOString();
        lastCommandError = null;
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Erro desconhecido ao executar comando';
        await failWorkerCommand(command.id, message);
        lastCommandRunAt = new Date().toISOString();
        lastCommandError = message;
        console.error('[notification-worker] Erro ao executar comando manual:', error);
      }
    }
  } catch (error) {
    lastCommandRunAt = new Date().toISOString();
    lastCommandError = error instanceof Error ? error.message : 'Erro desconhecido ao processar comandos';
    console.error('[notification-worker] Erro no loop de comandos manuais:', error);
  } finally {
    isProcessingCommands = false;
  }
}

async function boot() {
  await pool.query('SELECT 1');
  console.log('[notification-worker] Conectado ao banco');
  setInterval(() => {
    void processQueue();
  }, Number(NOTIFICATION_WORKER_INTERVAL_MS));
  setInterval(() => {
    void generateScheduledNotifications();
  }, Number(NOTIFICATION_GENERATOR_INTERVAL_MS));
  setInterval(() => {
    void processPendingWorkerCommands();
  }, Number(NOTIFICATION_COMMAND_POLL_INTERVAL_MS));
  await generateScheduledNotifications();
  await processQueue();
  await processPendingWorkerCommands();
}

const server = http.createServer((_, response) => {
  const missingSmtpEnvKeys = getMissingSmtpEnvKeys();
  const missingWhatsappEnvKeys = getMissingWhatsappEnvKeys();

  response.writeHead(200, { 'Content-Type': 'application/json' });
  response.end(JSON.stringify({
    status: 'ok',
    running: isRunning,
    generating: isGenerating,
    processingCommands: isProcessingCommands,
    lastRunAt,
    lastError,
    lastGenerationRunAt,
    lastGenerationError,
    lastCommandRunAt,
    lastCommandError,
    smtpConfigured: missingSmtpEnvKeys.length === 0,
    whatsappConfigured: missingWhatsappEnvKeys.length === 0,
    missingSmtpEnvKeys,
    missingWhatsappEnvKeys,
    workerIntervalMs: Number(NOTIFICATION_WORKER_INTERVAL_MS),
    generatorIntervalMs: Number(NOTIFICATION_GENERATOR_INTERVAL_MS),
    commandPollIntervalMs: Number(NOTIFICATION_COMMAND_POLL_INTERVAL_MS)
  }));
});

server.listen(Number(WORKER_HEALTH_PORT), () => {
  console.log(`[notification-worker] Health listening on :${WORKER_HEALTH_PORT}`);
});

boot().catch((error) => {
  console.error('[notification-worker] Falha no boot:', error);
  process.exit(1);
});
