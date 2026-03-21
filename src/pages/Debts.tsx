import * as React from 'react';
import { useAuth } from 'miaoda-auth-react';
import { useNavigate } from 'react-router-dom';
import {
  AlertCircle,
  ArrowRight,
  Calendar,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  CreditCard,
  DollarSign,
  Handshake,
  RefreshCw,
  RotateCcw,
  Scissors,
  Search,
  Target,
  Trash2,
  X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { useFinanceScope } from '@/hooks/useFinanceScope';
import {
  debtAbatementsApi,
  debtAgreementsApi,
  debtPaymentsApi,
  debtsApi,
} from '@/db/api';
import type {
  Debt,
  DebtAbatement,
  DebtAbatementType,
  DebtAgreement,
  DebtPayment,
  DebtPaymentMethod,
  DebtStatus,
} from '@/types/types';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

const STATUS_CONFIG: Record<DebtStatus, { label: string; className: string }> = {
  PENDENTE: { label: 'Pendente', className: 'bg-blue-100 text-blue-700' },
  VENCIDO: { label: 'Vencido', className: 'bg-red-100 text-red-700' },
  RENEGOCIADO: { label: 'Renegociado', className: 'bg-violet-100 text-violet-700' },
  PAGO: { label: 'Pago', className: 'bg-emerald-100 text-emerald-700' },
  CANCELADO: { label: 'Cancelado', className: 'bg-slate-100 text-slate-600' },
};

const DEBT_CATEGORIES = ['BANCARIAS', 'TRIBUTARIAS', 'FORNECEDORES', 'TRABALHISTAS', 'ESPECIAIS'];

const PAYMENT_METHODS: Array<{ value: DebtPaymentMethod; label: string }> = [
  { value: 'PIX', label: 'PIX' },
  { value: 'BOLETO', label: 'Boleto' },
  { value: 'CARTAO_CREDITO', label: 'Cartao de Credito' },
  { value: 'CARTAO_DEBITO', label: 'Cartao de Debito' },
  { value: 'TRANSFERENCIA', label: 'Transferencia' },
  { value: 'DINHEIRO', label: 'Dinheiro' },
  { value: 'DEBITO_AUTOMATICO', label: 'Debito Automatico' },
  { value: 'OUTRO', label: 'Outro' },
];

const ABATEMENT_TYPES: Array<{ value: DebtAbatementType; label: string; description: string }> = [
  { value: 'JUROS', label: 'Reducao de Juros', description: 'Desconto em juros acumulados' },
  { value: 'MULTA', label: 'Reducao de Multa', description: 'Desconto na multa por atraso' },
  { value: 'AMBOS', label: 'Juros + Multa', description: 'Reducao combinada' },
  { value: 'VALOR_PRINCIPAL', label: 'Valor Principal', description: 'Desconto no principal' },
];

type DebtDialogState =
  | { type: 'payment'; debt: Debt }
  | { type: 'agreement'; debt: Debt }
  | { type: 'abatement'; debt: Debt }
  | null;

function formatCurrency(value: number | string | null | undefined) {
  const numeric = Number(value ?? 0);
  return currencyFormatter.format(Number.isFinite(numeric) ? numeric : 0);
}

function formatDate(value: string | null | undefined) {
  if (!value) return '—';
  const normalized = value.length === 10 ? `${value}T12:00:00` : value;
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('pt-BR');
}

function projectBalance(originalAmount: number, interestRateDecimal: number, interestType: Debt['interest_type'], dueDate: string) {
  if (!originalAmount || !dueDate) return originalAmount || 0;
  const due = new Date(`${dueDate}T12:00:00`);
  const now = new Date();
  const diffMs = now.getTime() - due.getTime();
  const daysLate = Math.max(0, Math.ceil(diffMs / 86400000));

  if (daysLate === 0 || interestRateDecimal <= 0) {
    return Number(originalAmount.toFixed(2));
  }

  const dailyRate = interestRateDecimal / 30;
  const balance =
    interestType === 'SIMPLES'
      ? originalAmount * (1 + dailyRate * daysLate)
      : originalAmount * Math.pow(1 + dailyRate, daysLate);

  return Number(balance.toFixed(2));
}

function SummaryCard({
  title,
  value,
  subtitle,
  icon: Icon,
}: {
  title: string;
  value: string | number;
  subtitle: string;
  icon: LucideIcon;
}) {
  return (
    <div className="glass-card rounded-2xl p-4">
      <div className="mb-2 flex items-center justify-between">
        <p className="text-sm font-medium text-slate-500">{title}</p>
        <Icon size={16} className="text-slate-400" />
      </div>
      <p className="text-2xl font-bold tracking-tight text-slate-900">{value}</p>
      <p className="mt-1 text-xs text-slate-500">{subtitle}</p>
    </div>
  );
}

function StatusBadge({ status }: { status: DebtStatus }) {
  const config = STATUS_CONFIG[status];
  return (
    <span className={`rounded-md px-2 py-0.5 text-[11px] font-medium ${config.className}`}>
      {config.label}
    </span>
  );
}

function TabButton({
  active,
  label,
  count,
  onClick,
  icon: Icon,
}: {
  active: boolean;
  label: string;
  count?: number;
  onClick: () => void;
  icon: LucideIcon;
}) {
  return (
    <button
      onClick={onClick}
      className={`flex items-center gap-2 whitespace-nowrap border-b-2 px-5 py-3 text-sm font-bold transition-all ${
        active ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-700'
      }`}
    >
      <Icon size={15} />
      {label}
      {count !== undefined ? (
        <span
          className={`rounded-full px-1.5 py-0.5 text-[10px] font-black ${
            active ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'
          }`}
        >
          {count}
        </span>
      ) : null}
    </button>
  );
}

function DebtForm({
  userId,
  companyId,
  personId,
  isPJ,
  onSaved,
}: {
  userId: string;
  companyId: string | null | undefined;
  personId: string | undefined;
  isPJ: boolean;
  onSaved: () => Promise<void>;
}) {
  const [open, setOpen] = React.useState(true);
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');
  const [form, setForm] = React.useState({
    description: '',
    creditor: '',
    original_amount: '',
    interest_rate: '',
    interest_type: 'COMPOSTO' as Debt['interest_type'],
    penalty_rate: '',
    due_date: '',
    category: 'BANCARIAS',
    notes: '',
  });

  const projectedBalance = projectBalance(
    Number(form.original_amount || 0),
    Number(form.interest_rate || 0) / 100,
    form.interest_type,
    form.due_date
  );

  const setField = (field: keyof typeof form, value: string) => {
    setForm((current) => ({ ...current, [field]: value }));
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');
    setLoading(true);

    try {
      const originalAmount = Number(form.original_amount);
      const interestRate = Number(form.interest_rate || 0) / 100;
      const penaltyRate = Number(form.penalty_rate || 0) / 100;

      await debtsApi.create({
        user_id: userId,
        company_id: isPJ ? companyId ?? null : null,
        person_id: isPJ ? null : personId ?? null,
        description: form.description.trim(),
        creditor: form.creditor.trim(),
        original_amount: originalAmount,
        current_balance: projectBalance(originalAmount, interestRate, form.interest_type, form.due_date),
        interest_rate: interestRate,
        interest_type: form.interest_type,
        penalty_rate: penaltyRate,
        due_date: form.due_date,
        status: 'PENDENTE',
        category: form.category,
        notes: form.notes.trim() || null,
        total_paid: 0,
        total_abated: 0,
      });

      setForm({
        description: '',
        creditor: '',
        original_amount: '',
        interest_rate: '',
        interest_type: 'COMPOSTO',
        penalty_rate: '',
        due_date: '',
        category: 'BANCARIAS',
        notes: '',
      });

      await onSaved();
    } catch (submissionError) {
      setError(submissionError instanceof Error ? submissionError.message : 'Erro ao salvar divida');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="glass-card overflow-hidden rounded-2xl">
      <button
        onClick={() => setOpen((current) => !current)}
          className="flex w-full items-center justify-between border-b border-slate-100 bg-white px-6 py-4 text-slate-900 transition-colors hover:bg-slate-50"
      >
        <span className="flex items-center gap-2 text-sm font-semibold">
          <DollarSign size={16} className="text-slate-500" />
          Nova Divida
        </span>
        {open ? <ChevronUp size={16} className="text-slate-400" /> : <ChevronDown size={16} className="text-slate-400" />}
      </button>

      {open ? (
        <form onSubmit={handleSubmit} className="space-y-4 bg-white p-6">
          <div className="flex flex-col items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4 text-center shadow-sm sm:flex-row">
            <p className="text-sm font-medium text-slate-500">Saldo projetado</p>
            <p className="text-xl font-bold text-slate-900">{formatCurrency(projectedBalance)}</p>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium text-slate-700">Descricao</label>
            <input
              required
              type="text"
              value={form.description}
              onChange={(event) => setField('description', event.target.value)}
              className="premium-input"
              placeholder="Ex: Emprestimo de capital de giro"
            />
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium text-slate-700">Credor</label>
            <input
              required
              type="text"
              value={form.creditor}
              onChange={(event) => setField('creditor', event.target.value)}
              className="premium-input"
              placeholder="Banco, fornecedor ou pessoa"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-slate-700">Valor original</label>
              <input
                required
                min="0.01"
                step="0.01"
                type="number"
                value={form.original_amount}
                onChange={(event) => setField('original_amount', event.target.value)}
                className="premium-input"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-slate-700">Vencimento</label>
              <input
                required
                type="date"
                value={form.due_date}
                onChange={(event) => setField('due_date', event.target.value)}
                className="premium-input"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-slate-700">Juros (% a.m.)</label>
              <input
                min="0"
                step="0.0001"
                type="number"
                value={form.interest_rate}
                onChange={(event) => setField('interest_rate', event.target.value)}
                className="premium-input"
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-slate-700">Multa (%)</label>
              <input
                min="0"
                step="0.01"
                type="number"
                value={form.penalty_rate}
                onChange={(event) => setField('penalty_rate', event.target.value)}
                className="premium-input"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-slate-700">Capitalizacao</label>
              <select
                value={form.interest_type}
                onChange={(event) => setField('interest_type', event.target.value as Debt['interest_type'])}
                className="premium-input"
              >
                <option value="COMPOSTO">Juros compostos</option>
                <option value="SIMPLES">Juros simples</option>
              </select>
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-slate-700">Categoria</label>
              <select
                value={form.category}
                onChange={(event) => setField('category', event.target.value)}
                className="premium-input"
              >
                {DEBT_CATEGORIES.map((category) => (
                  <option key={category} value={category}>
                    {category}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium text-slate-700">Observacoes</label>
            <textarea
              rows={3}
              value={form.notes}
              onChange={(event) => setField('notes', event.target.value)}
              className="premium-input resize-none"
              placeholder="Condicoes da divida, garantias ou observacoes"
            />
          </div>

          {error ? (
            <div className="flex items-center gap-2 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-red-600">
              <AlertCircle size={14} />
              <p className="text-sm font-medium">{error}</p>
            </div>
          ) : null}

          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center gap-3 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-slate-50 transition-colors hover:bg-slate-800 disabled:pointer-events-none disabled:opacity-50"
          >
            {loading ? <div className="h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white" /> : <DollarSign size={16} />}
            Salvar divida
          </button>
        </form>
      ) : null}
    </div>
  );
}

function PaymentDialog({
  debt,
  onClose,
  onSuccess,
}: {
  debt: Debt;
  onClose: () => void;
  onSuccess: () => Promise<void>;
}) {
  const [form, setForm] = React.useState({
    amount: '',
    method: 'PIX' as DebtPaymentMethod,
    reference: '',
    notes: '',
  });
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  const currentBalance = Number(debt.current_balance || 0);
  const paymentAmount = Number(form.amount || 0);
  const resultingBalance = Math.max(0, currentBalance - paymentAmount);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');

    if (paymentAmount <= 0) {
      setError('Informe um valor de pagamento valido.');
      return;
    }

    setLoading(true);
    try {
      await debtPaymentsApi.register(debt.id, paymentAmount, form.method, form.reference || null, form.notes || null);
      await onSuccess();
    } catch (submissionError) {
      setError(submissionError instanceof Error ? submissionError.message : 'Erro ao registrar pagamento');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 backdrop-blur-sm sm:items-center">
      <div className="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-100 p-6">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50">
              <DollarSign size={20} className="text-emerald-600" />
            </div>
            <div>
              <h2 className="text-base font-bold text-slate-900">Registrar pagamento</h2>
              <p className="max-w-[180px] truncate text-xs text-slate-400">{debt.description}</p>
            </div>
          </div>
          <button onClick={onClose} className="rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
            <X size={18} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5 p-6">
          <div className="grid grid-cols-3 gap-3 text-center">
            <div className="rounded-2xl border border-red-100 bg-red-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-red-400">Saldo atual</p>
              <p className="text-sm font-black text-red-700">{formatCurrency(currentBalance)}</p>
            </div>
            <div className="rounded-2xl border border-blue-100 bg-blue-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-blue-400">Pagamento</p>
              <p className="text-sm font-black text-blue-700">{formatCurrency(paymentAmount)}</p>
            </div>
            <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-emerald-400">Saldo final</p>
              <p className="text-sm font-black text-emerald-700">{formatCurrency(resultingBalance)}</p>
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Valor *</label>
            <input
              required
              min="0.01"
              step="0.01"
              type="number"
              value={form.amount}
              onChange={(event) => setForm((current) => ({ ...current, amount: event.target.value }))}
              className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 text-lg font-bold text-slate-900 outline-none transition-colors focus:border-blue-500"
            />
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Forma de pagamento *</label>
            <select
              value={form.method}
              onChange={(event) => setForm((current) => ({ ...current, method: event.target.value as DebtPaymentMethod }))}
              className="w-full rounded-2xl border-2 border-slate-200 bg-white px-5 py-3.5 font-semibold text-slate-900 outline-none transition-colors focus:border-blue-500"
            >
              {PAYMENT_METHODS.map((method) => (
                <option key={method.value} value={method.value}>
                  {method.label}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Referencia</label>
            <input
              type="text"
              value={form.reference}
              onChange={(event) => setForm((current) => ({ ...current, reference: event.target.value }))}
              className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-medium text-slate-900 outline-none transition-colors focus:border-blue-500"
              placeholder="Comprovante ou identificador"
            />
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Observacoes</label>
            <textarea
              rows={2}
              value={form.notes}
              onChange={(event) => setForm((current) => ({ ...current, notes: event.target.value }))}
              className="w-full resize-none rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-medium text-slate-900 outline-none transition-colors focus:border-blue-500"
            />
          </div>

          {error ? (
            <div className="flex items-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-red-600">
              <AlertCircle size={16} />
              <p className="text-sm font-semibold">{error}</p>
            </div>
          ) : null}

          <div className="flex gap-3">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 rounded-2xl border-2 border-slate-200 py-3.5 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading}
              className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3.5 text-sm font-bold text-white transition-colors hover:bg-emerald-500 disabled:opacity-50"
            >
              {loading ? <div className="h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white" /> : <CheckCircle2 size={17} />}
              Confirmar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function AgreementDialog({
  debt,
  userId,
  onClose,
  onSuccess,
}: {
  debt: Debt;
  userId: string;
  onClose: () => void;
  onSuccess: () => Promise<void>;
}) {
  const currentBalance = Number(debt.current_balance || 0);
  const [form, setForm] = React.useState({
    agreed_amount: currentBalance.toFixed(2),
    installments: '1',
    new_interest_rate: '0',
    start_date: new Date().toISOString().split('T')[0],
    terms: '',
  });
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  const agreedAmount = Number(form.agreed_amount || 0);
  const installments = Math.max(1, Number.parseInt(form.installments || '1', 10) || 1);
  const installmentValue = installments > 0 ? agreedAmount / installments : 0;
  const discount = Math.max(0, currentBalance - agreedAmount);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');

    if (agreedAmount <= 0) {
      setError('Informe o valor acordado.');
      return;
    }

    setLoading(true);
    try {
      await debtAgreementsApi.create({
        debt_id: debt.id,
        user_id: userId,
        balance_at_agreement: currentBalance,
        agreed_amount: agreedAmount,
        discount_applied: discount,
        installments,
        installment_value: Number(installmentValue.toFixed(2)),
        new_interest_rate: Number(form.new_interest_rate || 0) / 100,
        start_date: form.start_date,
        end_date: null,
        status: 'ATIVO',
        terms: form.terms.trim() || null,
      });

      await debtsApi.update(debt.id, {
        current_balance: agreedAmount,
        status: 'RENEGOCIADO',
        interest_rate: Number(form.new_interest_rate || 0) / 100,
      });

      await onSuccess();
    } catch (submissionError) {
      setError(submissionError instanceof Error ? submissionError.message : 'Erro ao registrar acordo');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 backdrop-blur-sm sm:items-center">
      <div className="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-100 p-6">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-violet-100 bg-violet-50">
              <Handshake size={20} className="text-violet-600" />
            </div>
            <div>
              <h2 className="text-base font-bold text-slate-900">Novo acordo</h2>
              <p className="max-w-[180px] truncate text-xs text-slate-400">{debt.description}</p>
            </div>
          </div>
          <button onClick={onClose} className="rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
            <X size={18} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5 p-6">
          <div className="grid grid-cols-3 gap-3 text-center">
            <div className="rounded-2xl border border-red-100 bg-red-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-red-400">Saldo</p>
              <p className="text-sm font-black text-red-700">{formatCurrency(currentBalance)}</p>
            </div>
            <div className="rounded-2xl border border-violet-100 bg-violet-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-violet-400">Desconto</p>
              <p className="text-sm font-black text-violet-700">{formatCurrency(discount)}</p>
            </div>
            <div className="rounded-2xl border border-blue-100 bg-blue-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-blue-400">Parcela</p>
              <p className="text-sm font-black text-blue-700">{formatCurrency(installmentValue)}</p>
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Valor acordado *</label>
            <input
              required
              min="0.01"
              step="0.01"
              type="number"
              value={form.agreed_amount}
              onChange={(event) => setForm((current) => ({ ...current, agreed_amount: event.target.value }))}
              className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 text-lg font-bold text-slate-900 outline-none transition-colors focus:border-violet-500"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Parcelas *</label>
              <input
                required
                min="1"
                max="360"
                type="number"
                value={form.installments}
                onChange={(event) => setForm((current) => ({ ...current, installments: event.target.value }))}
                className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-bold text-slate-900 outline-none transition-colors focus:border-violet-500"
              />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Nova taxa %</label>
              <input
                min="0"
                step="0.01"
                type="number"
                value={form.new_interest_rate}
                onChange={(event) => setForm((current) => ({ ...current, new_interest_rate: event.target.value }))}
                className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-bold text-slate-900 outline-none transition-colors focus:border-violet-500"
              />
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Inicio *</label>
            <input
              required
              type="date"
              value={form.start_date}
              onChange={(event) => setForm((current) => ({ ...current, start_date: event.target.value }))}
              className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-semibold text-slate-900 outline-none transition-colors focus:border-violet-500"
            />
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Condicoes</label>
            <textarea
              rows={3}
              value={form.terms}
              onChange={(event) => setForm((current) => ({ ...current, terms: event.target.value }))}
              className="w-full resize-none rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-medium text-slate-900 outline-none transition-colors focus:border-violet-500"
            />
          </div>

          {error ? (
            <div className="flex items-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-red-600">
              <AlertCircle size={16} />
              <p className="text-sm font-semibold">{error}</p>
            </div>
          ) : null}

          <div className="flex gap-3">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 rounded-2xl border-2 border-slate-200 py-3.5 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading}
              className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-violet-600 py-3.5 text-sm font-bold text-white transition-colors hover:bg-violet-500 disabled:opacity-50"
            >
              {loading ? <div className="h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white" /> : <Handshake size={17} />}
              Registrar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function AbatementDialog({
  debt,
  onClose,
  onSuccess,
}: {
  debt: Debt;
  onClose: () => void;
  onSuccess: () => Promise<void>;
}) {
  const currentBalance = Number(debt.current_balance || 0);
  const [form, setForm] = React.useState({
    abatement_type: 'JUROS' as DebtAbatementType,
    amount: '',
    reason: '',
  });
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  const amount = Number(form.amount || 0);
  const nextBalance = Math.max(0, currentBalance - amount);
  const percentage = currentBalance > 0 ? ((amount / currentBalance) * 100).toFixed(1) : '0.0';

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');

    if (amount <= 0) {
      setError('Informe o valor do abatimento.');
      return;
    }

    if (!form.reason.trim()) {
      setError('Informe o motivo do abatimento.');
      return;
    }

    setLoading(true);
    try {
      await debtAbatementsApi.apply(debt.id, form.abatement_type, amount, form.reason.trim());
      await onSuccess();
    } catch (submissionError) {
      setError(submissionError instanceof Error ? submissionError.message : 'Erro ao aplicar abatimento');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 backdrop-blur-sm sm:items-center">
      <div className="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-100 p-6">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-100 bg-amber-50">
              <Scissors size={20} className="text-amber-600" />
            </div>
            <div>
              <h2 className="text-base font-bold text-slate-900">Aplicar abatimento</h2>
              <p className="max-w-[180px] truncate text-xs text-slate-400">{debt.description}</p>
            </div>
          </div>
          <button onClick={onClose} className="rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-700">
            <X size={18} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5 p-6">
          <div className="grid grid-cols-3 gap-3 text-center">
            <div className="rounded-2xl border border-red-100 bg-red-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-red-400">Saldo</p>
              <p className="text-sm font-black text-red-700">{formatCurrency(currentBalance)}</p>
            </div>
            <div className="rounded-2xl border border-amber-100 bg-amber-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-amber-500">Abatimento</p>
              <p className="text-sm font-black text-amber-700">{formatCurrency(amount)}</p>
              <p className="text-[10px] text-amber-500">({percentage}%)</p>
            </div>
            <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-3">
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-emerald-400">Saldo final</p>
              <p className="text-sm font-black text-emerald-700">{formatCurrency(nextBalance)}</p>
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Tipo *</label>
            <div className="grid grid-cols-2 gap-2">
              {ABATEMENT_TYPES.map((type) => (
                <button
                  key={type.value}
                  type="button"
                  onClick={() => setForm((current) => ({ ...current, abatement_type: type.value }))}
                  className={`rounded-2xl border-2 p-3 text-left transition-all ${
                    form.abatement_type === type.value ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-white hover:border-slate-300'
                  }`}
                >
                  <p className="text-xs font-bold text-slate-800">{type.label}</p>
                  <p className="mt-0.5 text-[10px] text-slate-400">{type.description}</p>
                </button>
              ))}
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Valor *</label>
            <input
              required
              min="0.01"
              step="0.01"
              type="number"
              value={form.amount}
              onChange={(event) => setForm((current) => ({ ...current, amount: event.target.value }))}
              className="w-full rounded-2xl border-2 border-slate-200 px-5 py-3.5 text-lg font-bold text-slate-900 outline-none transition-colors focus:border-amber-500"
            />
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Motivo *</label>
            <textarea
              required
              rows={3}
              value={form.reason}
              onChange={(event) => setForm((current) => ({ ...current, reason: event.target.value }))}
              className="w-full resize-none rounded-2xl border-2 border-slate-200 px-5 py-3.5 font-medium text-slate-900 outline-none transition-colors focus:border-amber-500"
            />
          </div>

          {error ? (
            <div className="flex items-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-red-600">
              <AlertCircle size={16} />
              <p className="text-sm font-semibold">{error}</p>
            </div>
          ) : null}

          <div className="flex gap-3">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 rounded-2xl border-2 border-slate-200 py-3.5 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading}
              className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-amber-500 py-3.5 text-sm font-bold text-white transition-colors hover:bg-amber-400 disabled:opacity-50"
            >
              {loading ? <div className="h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white" /> : <Scissors size={17} />}
              Aplicar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function DebtDetailPanel({
  debt,
  onClose,
  onActionTrigger,
}: {
  debt: Debt;
  onClose: () => void;
  onActionTrigger: (type: 'payment' | 'agreement' | 'abatement', debt: Debt) => void;
}) {
  const [activeTab, setActiveTab] = React.useState<'payments' | 'agreements' | 'abatements' | 'info'>('payments');
  const [payments, setPayments] = React.useState<DebtPayment[]>([]);
  const [agreements, setAgreements] = React.useState<DebtAgreement[]>([]);
  const [abatements, setAbatements] = React.useState<DebtAbatement[]>([]);
  const [loading, setLoading] = React.useState(true);

  const loadData = React.useCallback(async () => {
    setLoading(true);
    try {
      const [paymentRows, agreementRows, abatementRows] = await Promise.all([
        debtPaymentsApi.getByDebt(debt.id).catch(() => [] as DebtPayment[]),
        debtAgreementsApi.getByDebt(debt.id).catch(() => [] as DebtAgreement[]),
        debtAbatementsApi.getByDebt(debt.id).catch(() => [] as DebtAbatement[]),
      ]);
      setPayments(paymentRows);
      setAgreements(agreementRows);
      setAbatements(abatementRows);
    } finally {
      setLoading(false);
    }
  }, [debt.id]);

  React.useEffect(() => {
    void loadData();
  }, [loadData]);

  const originalAmount = Number(debt.original_amount || 0);
  const currentBalance = Number(debt.current_balance || 0);
  const totalPaid = Number(debt.total_paid || 0);
  const totalAbated = Number(debt.total_abated || 0);
  const paidProgress = originalAmount > 0 ? Math.min(100, (totalPaid / originalAmount) * 100) : 0;
  const statusConfig = STATUS_CONFIG[debt.status];

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />

      <div className="relative flex h-full w-full max-w-lg flex-col overflow-hidden bg-white shadow-2xl">
        <div className="flex-shrink-0 bg-gradient-to-br from-slate-900 to-slate-800 p-6">
          <div className="mb-5 flex items-start justify-between">
            <span className={`rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest ${statusConfig.className}`}>
              {statusConfig.label}
            </span>
            <button onClick={onClose} className="rounded-xl bg-white/10 p-2 text-white transition-colors hover:bg-white/20">
              <X size={17} />
            </button>
          </div>

          <h2 className="mb-1 text-xl font-black leading-tight text-white">{debt.description}</h2>
          <p className="mb-6 text-sm font-semibold text-slate-400">{debt.creditor}</p>

          <div className="grid grid-cols-3 gap-3">
            <div>
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">Saldo</p>
              <p className="text-xl font-black text-white">{formatCurrency(currentBalance)}</p>
            </div>
            <div>
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">Pago</p>
              <p className="text-xl font-black text-emerald-400">{formatCurrency(totalPaid)}</p>
            </div>
            <div>
              <p className="mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">Abatido</p>
              <p className="text-xl font-black text-amber-400">{formatCurrency(totalAbated)}</p>
            </div>
          </div>

          <div className="mt-5">
            <div className="mb-2 flex justify-between text-[10px] font-bold text-slate-400">
              <span>Progresso de pagamento</span>
              <span>{paidProgress.toFixed(1)}%</span>
            </div>
            <div className="progress-bg bg-white/10">
              <div className="h-full rounded-full bg-emerald-400" style={{ width: `${paidProgress}%` }} />
            </div>
          </div>

          <div className="mt-4 flex items-center gap-2 text-xs font-semibold text-slate-400">
            <Calendar size={13} />
            <span>Vencimento: {formatDate(debt.due_date)}</span>
            {debt.category ? (
              <>
                <span>·</span>
                <span>{debt.category}</span>
              </>
            ) : null}
          </div>
        </div>

        <div className="flex flex-shrink-0 gap-2 border-b border-slate-100 bg-white px-4 py-3">
          <button
            onClick={() => onActionTrigger('payment', debt)}
            className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-2.5 text-xs font-bold text-white transition-colors hover:bg-emerald-500"
          >
            <DollarSign size={14} />
            Pagamento
          </button>
          <button
            onClick={() => onActionTrigger('agreement', debt)}
            className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-violet-600 py-2.5 text-xs font-bold text-white transition-colors hover:bg-violet-500"
          >
            <Handshake size={14} />
            Acordo
          </button>
          <button
            onClick={() => onActionTrigger('abatement', debt)}
            className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-amber-500 py-2.5 text-xs font-bold text-white transition-colors hover:bg-amber-400"
          >
            <Scissors size={14} />
            Abatimento
          </button>
        </div>

        <div className="flex flex-shrink-0 overflow-x-auto border-b border-slate-100">
          <TabButton active={activeTab === 'payments'} onClick={() => setActiveTab('payments')} icon={CreditCard} label="Pagamentos" count={payments.length} />
          <TabButton active={activeTab === 'agreements'} onClick={() => setActiveTab('agreements')} icon={Handshake} label="Acordos" count={agreements.length} />
          <TabButton active={activeTab === 'abatements'} onClick={() => setActiveTab('abatements')} icon={Scissors} label="Abatimentos" count={abatements.length} />
          <TabButton active={activeTab === 'info'} onClick={() => setActiveTab('info')} icon={DollarSign} label="Detalhes" />
        </div>

        <div className="flex-1 overflow-y-auto">
          {loading ? (
            <div className="flex h-40 items-center justify-center">
              <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600" />
            </div>
          ) : null}

          {!loading && activeTab === 'payments' ? (
            <div className="space-y-3 p-4">
              {payments.length === 0 ? (
                <EmptyPanel icon={CreditCard} title="Nenhum pagamento registrado" subtitle="Use a acao Pagamento para registrar amortizacoes." />
              ) : (
                payments.map((payment) => (
                  <div key={payment.id} className="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100">
                      <CheckCircle2 size={18} className="text-emerald-600" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-bold text-slate-900">{formatCurrency(payment.amount)}</p>
                      <p className="mt-0.5 text-xs font-semibold text-slate-400">
                        {PAYMENT_METHODS.find((item) => item.value === payment.method)?.label || payment.method} · {formatDate(payment.payment_date)}
                      </p>
                      {payment.reference ? <p className="mt-1 truncate text-[10px] text-slate-300">#{payment.reference}</p> : null}
                    </div>
                  </div>
                ))
              )}
            </div>
          ) : null}

          {!loading && activeTab === 'agreements' ? (
            <div className="space-y-3 p-4">
              {agreements.length === 0 ? (
                <EmptyPanel icon={Handshake} title="Nenhum acordo registrado" subtitle="Use a acao Acordo para renegociar a divida." />
              ) : (
                agreements.map((agreement) => (
                  <div key={agreement.id} className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div className="mb-3 flex items-center justify-between">
                      <span className={`rounded-full border px-2 py-0.5 text-[10px] font-black uppercase ${
                        agreement.status === 'ATIVO'
                          ? 'border-violet-100 bg-violet-50 text-violet-700'
                          : agreement.status === 'CONCLUIDO'
                            ? 'border-emerald-100 bg-emerald-50 text-emerald-700'
                            : 'border-slate-200 bg-slate-100 text-slate-500'
                      }`}>
                        {agreement.status}
                      </span>
                      <span className="text-xs font-semibold text-slate-400">{formatDate(agreement.start_date)}</span>
                    </div>
                    <div className="grid grid-cols-3 gap-2 text-center">
                      <MetricBlock label="Acordado" value={formatCurrency(agreement.agreed_amount)} />
                      <MetricBlock label="Parcelas" value={`${agreement.installments}x`} />
                      <MetricBlock label="Desconto" value={formatCurrency(agreement.discount_applied)} accent="text-violet-600" />
                    </div>
                    {agreement.terms ? <p className="mt-3 border-t border-slate-100 pt-3 text-xs italic text-slate-400">{agreement.terms}</p> : null}
                  </div>
                ))
              )}
            </div>
          ) : null}

          {!loading && activeTab === 'abatements' ? (
            <div className="space-y-3 p-4">
              {abatements.length === 0 ? (
                <EmptyPanel icon={Scissors} title="Nenhum abatimento registrado" subtitle="Use a acao Abatimento para aplicar descontos." />
              ) : (
                abatements.map((abatement) => (
                  <div key={abatement.id} className="flex items-center gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
                      <Scissors size={18} className="text-amber-600" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-bold text-slate-900">{formatCurrency(abatement.amount)}</p>
                      <p className="mt-0.5 text-xs font-semibold text-slate-400">
                        {ABATEMENT_TYPES.find((item) => item.value === abatement.abatement_type)?.label || abatement.abatement_type}
                      </p>
                      <p className="mt-1 text-[11px] text-slate-500">{abatement.reason}</p>
                    </div>
                  </div>
                ))
              )}
            </div>
          ) : null}

          {!loading && activeTab === 'info' ? (
            <div className="space-y-3 p-4">
              {[
                { label: 'Valor original', value: formatCurrency(debt.original_amount) },
                { label: 'Saldo devedor', value: formatCurrency(debt.current_balance), accent: 'text-red-600' },
                { label: 'Credor', value: debt.creditor },
                { label: 'Categoria', value: debt.category || 'GERAL' },
                { label: 'Taxa de juros', value: `${(Number(debt.interest_rate || 0) * 100).toFixed(4)}% a.m.` },
                { label: 'Capitalizacao', value: debt.interest_type },
                { label: 'Multa', value: `${(Number(debt.penalty_rate || 0) * 100).toFixed(2)}%` },
                { label: 'Vencimento', value: formatDate(debt.due_date) },
                { label: 'Criado em', value: formatDate(debt.created_at) },
              ].map((row) => (
                <div key={row.label} className="flex items-center justify-between border-b border-slate-50 py-3 last:border-0">
                  <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">{row.label}</span>
                  <span className={`text-sm font-bold text-slate-800 ${row.accent || ''}`}>{row.value}</span>
                </div>
              ))}
              {debt.notes ? (
                <div className="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                  <p className="mb-2 text-[10px] font-black uppercase tracking-widest text-blue-400">Observacoes</p>
                  <p className="text-sm text-slate-700">{debt.notes}</p>
                </div>
              ) : null}
            </div>
          ) : null}
        </div>

        <div className="flex-shrink-0 border-t border-slate-100 p-4">
          <button
            onClick={() => void loadData()}
            className="flex w-full items-center justify-center gap-2 py-3 text-xs font-bold text-slate-400 transition-colors hover:text-slate-700"
          >
            <RefreshCw size={13} />
            Atualizar dados
          </button>
        </div>
      </div>
    </div>
  );
}

function MetricBlock({ label, value, accent }: { label: string; value: string; accent?: string }) {
  return (
    <div>
      <p className="mb-0.5 text-[10px] font-bold text-slate-400">{label}</p>
      <p className={`text-sm font-black text-slate-800 ${accent || ''}`}>{value}</p>
    </div>
  );
}

function EmptyPanel({
  icon: Icon,
  title,
  subtitle,
}: {
  icon: LucideIcon;
  title: string;
  subtitle: string;
}) {
  return (
    <div className="py-12 text-center text-slate-400">
      <Icon size={36} className="mx-auto mb-3 opacity-30" />
      <p className="text-sm font-semibold">{title}</p>
      <p className="mt-1 text-xs">{subtitle}</p>
    </div>
  );
}

export default function Debts() {
  const navigate = useNavigate();
  const { toast } = useToast();
  const { user } = useAuth();
  const { companyId, personId, isPJ } = useFinanceScope();
  const [debts, setDebts] = React.useState<Debt[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState('');
  const [search, setSearch] = React.useState('');
  const [statusFilter, setStatusFilter] = React.useState<'TODOS' | DebtStatus>('TODOS');
  const [selectedDebt, setSelectedDebt] = React.useState<Debt | null>(null);
  const [dialog, setDialog] = React.useState<DebtDialogState>(null);
  const debtFormRef = React.useRef<HTMLDivElement | null>(null);

  const loadDebts = React.useCallback(async () => {
    if (!user?.id) return;

    setLoading(true);
    setError('');
    try {
      const rows = await debtsApi.getAll(user.id, companyId, personId);
      setDebts(rows);
    } catch (loadError) {
      const message = loadError instanceof Error ? loadError.message : 'Erro ao carregar dividas';
      setError(message);
      toast({
        title: 'Erro',
        description: message,
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  }, [user?.id, companyId, personId, toast]);

  React.useEffect(() => {
    void loadDebts();
  }, [loadDebts]);

  const filteredDebts = debts.filter((debt) => {
    const normalizedSearch = search.trim().toLowerCase();
    const matchesStatus = statusFilter === 'TODOS' || debt.status === statusFilter;
    const matchesSearch =
      normalizedSearch.length === 0 ||
      debt.description.toLowerCase().includes(normalizedSearch) ||
      debt.creditor.toLowerCase().includes(normalizedSearch);

    return matchesStatus && matchesSearch;
  });

  const totalBalance = debts.reduce((sum, debt) => sum + Number(debt.current_balance || 0), 0);
  const totalPaid = debts.reduce((sum, debt) => sum + Number(debt.total_paid || 0), 0);
  const totalAbated = debts.reduce((sum, debt) => sum + Number(debt.total_abated || 0), 0);
  const overdueCount = debts.filter((debt) => debt.status === 'VENCIDO').length;
  const prefix = isPJ && companyId ? `/pj/${companyId}` : '/pf';

  const handleDelete = async (debt: Debt) => {
    const confirmed = window.confirm(
      `Deseja excluir a divida "${debt.description}"? Isso tambem remove pagamentos, acordos e abatimentos relacionados.`
    );

    if (!confirmed) return;

    try {
      await debtsApi.delete(debt.id);
      await loadDebts();
    } catch (deleteError) {
      const message = deleteError instanceof Error ? deleteError.message : 'Erro ao excluir divida';
      toast({
        title: 'Erro',
        description: message,
        variant: 'destructive',
      });
    }
  };

  const handleActionSuccess = async () => {
    setDialog(null);
    await loadDebts();
  };

  const scrollToDebtForm = () => {
    debtFormRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const statusTabs: Array<{ key: 'TODOS' | DebtStatus; label: string }> = [
    { key: 'TODOS', label: 'Todas' },
    { key: 'PENDENTE', label: 'Abertas' },
    { key: 'VENCIDO', label: 'Atrasadas' },
    { key: 'RENEGOCIADO', label: 'Acordos' },
    { key: 'PAGO', label: 'Liquidadas' },
  ];

  return (
    <div className="min-h-screen bg-slate-50 p-4 text-slate-900 sm:p-6 lg:p-8">
      <div className="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900 lg:text-3xl">Gestao de Dividas</h1>
          <p className="mt-1 text-sm text-slate-500">
            Visao consolidada das obrigacoes financeiras do escopo {isPJ ? 'empresarial' : 'pessoal'}.
          </p>
        </div>
        <button
          onClick={() => void loadDebts()}
          className="flex h-9 items-center justify-center rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
        >
          <RotateCcw size={14} className="mr-2" />
          Sincronizar
        </button>
      </div>

      <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <SummaryCard title="Balanco devedor" value={formatCurrency(totalBalance)} subtitle="Total atualizado das dividas abertas" icon={Target} />
        <SummaryCard title="Titulos vencidos" value={overdueCount} subtitle={`${overdueCount} registros em atraso`} icon={AlertCircle} />
        <SummaryCard title="Total amortizado" value={formatCurrency(totalPaid)} subtitle="Pagamentos ja registrados" icon={CheckCircle2} />
        <SummaryCard title="Total abatido" value={formatCurrency(totalAbated)} subtitle="Descontos, juros e perdoes" icon={Scissors} />
      </div>

      <div className="grid grid-cols-1 gap-8 xl:grid-cols-12">
        <div className="space-y-4 xl:col-span-8">
          <div className="glass-card rounded-2xl p-4">
            <div className="flex flex-col items-center gap-4 sm:flex-row">
              <div className="flex w-full flex-1 items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 transition-all focus-within:border-slate-400 focus-within:ring-1 focus-within:ring-slate-400">
                <Search size={16} className="text-slate-400" />
                <input
                  type="text"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  className="h-6 flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400"
                  placeholder="Localizar credor ou contrato..."
                />
                {search ? (
                  <button onClick={() => setSearch('')} className="text-slate-400 transition-colors hover:text-slate-600">
                    <X size={14} />
                  </button>
                ) : null}
              </div>

              <div className="hidden h-6 w-px bg-slate-200 sm:block" />

              <div className="flex flex-wrap gap-1">
                {statusTabs.map((tab) => (
                  <button
                    key={tab.key}
                    onClick={() => setStatusFilter(tab.key)}
                    className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                      statusFilter === tab.key ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50'
                    }`}
                  >
                    {tab.label}
                  </button>
                ))}
              </div>
            </div>
          </div>

          <div className="glass-card overflow-hidden rounded-2xl">
            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
              <h2 className="text-base font-semibold text-slate-800">Lista de passivos</h2>
              <span className="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500">
                {filteredDebts.length} ativos
              </span>
            </div>

            {loading ? (
              <div className="flex flex-col items-center justify-center gap-3 py-24">
                <div className="h-10 w-10 animate-spin rounded-full border-[3px] border-slate-100 border-t-blue-600" />
                <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">Carregando dados estruturados...</p>
              </div>
            ) : filteredDebts.length === 0 ? (
              <div className="py-24 text-center">
                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-[2rem] border border-slate-100 bg-slate-50">
                  <RotateCcw size={24} className="text-slate-300" />
                </div>
                {debts.length === 0 && !search && statusFilter === 'TODOS' ? (
                  <>
                    <p className="text-base font-black text-slate-700">Nenhuma dívida cadastrada</p>
                    <p className="mt-1 text-xs font-medium text-slate-400">
                      Comece registrando o primeiro passivo ou use contas a pagar quando a obrigação ainda não precisa de gestão detalhada.
                    </p>
                    <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-center">
                      <button
                        onClick={scrollToDebtForm}
                        className="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800"
                      >
                        Cadastrar Primeira Dívida
                      </button>
                      <button
                        onClick={() => navigate(`${prefix}/bills-to-pay`)}
                        className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
                      >
                        Ir para Contas a Pagar
                      </button>
                    </div>
                  </>
                ) : (
                  <>
                    <p className="text-base font-black text-slate-700">Nada no radar</p>
                    <p className="mt-1 text-xs font-medium text-slate-400">
                      {error || 'Nenhum titulo corresponde aos filtros atuais.'}
                    </p>
                  </>
                )}
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="premium-table">
                  <thead>
                    <tr>
                      <th>Identificacao</th>
                      <th className="hidden text-left md:table-cell">Vencimento</th>
                      <th className="text-left">Status</th>
                      <th className="text-right">Montante / Saldo</th>
                      <th className="hidden lg:table-cell">Amortizacao</th>
                      <th className="text-right">Painel</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredDebts.map((debt) => {
                      const currentBalance = Number(debt.current_balance || 0);
                      const totalPaidAmount = Number(debt.total_paid || 0);
                      const originalAmount = Number(debt.original_amount || 0);
                      const progress = originalAmount > 0 ? Math.min(100, (totalPaidAmount / originalAmount) * 100) : 0;
                      const highlightDebt = debt.status === 'VENCIDO';

                      return (
                        <tr key={debt.id} className="group cursor-pointer" onClick={() => setSelectedDebt(debt)}>
                          <td className="min-w-[220px]">
                            <p className="mb-0.5 font-medium text-slate-900 group-hover:underline">{debt.description}</p>
                            <div className="flex items-center gap-2">
                              <span className="text-xs text-slate-500">{debt.creditor}</span>
                              <span className="text-xs text-slate-400">• {debt.category || 'GERAL'}</span>
                            </div>
                          </td>
                          <td className="hidden text-left md:table-cell">
                            <span className="text-sm text-slate-600">{formatDate(debt.due_date)}</span>
                          </td>
                          <td className="text-left">
                            <StatusBadge status={debt.status} />
                          </td>
                          <td className="text-right">
                            <p className={`mb-0.5 text-sm font-semibold ${highlightDebt ? 'text-red-600' : 'text-slate-900'}`}>
                              {formatCurrency(currentBalance)}
                            </p>
                            <span className="text-xs text-slate-500">Original: {formatCurrency(originalAmount)}</span>
                          </td>
                          <td className="hidden min-w-[140px] lg:table-cell">
                            <div className="mt-1 flex flex-col gap-1.5">
                              <div className="progress-bg">
                                <div className="progress-fill" style={{ width: `${progress}%` }} />
                              </div>
                              <div className="text-[10px] font-medium text-slate-500">{progress.toFixed(0)}% concluido</div>
                            </div>
                          </td>
                          <td className="text-right" onClick={(event) => event.stopPropagation()}>
                            <div className="flex items-center justify-end gap-1">
                              <button
                                onClick={() => setDialog({ type: 'payment', debt })}
                                className="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                                title="Registrar pagamento"
                              >
                                <DollarSign size={14} />
                              </button>
                              <button
                                onClick={() => setDialog({ type: 'agreement', debt })}
                                className="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                                title="Registrar acordo"
                              >
                                <Handshake size={14} />
                              </button>
                              <button
                                onClick={() => setSelectedDebt(debt)}
                                className="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                                title="Ver detalhes"
                              >
                                <ArrowRight size={14} />
                              </button>
                              <button
                                onClick={() => void handleDelete(debt)}
                                className="flex h-8 w-8 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                title="Excluir divida"
                              >
                                <Trash2 size={14} />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        <div ref={debtFormRef} className="w-full xl:col-span-4">
          <div className="space-y-4 xl:sticky xl:top-24">
            {user?.id ? (
              <DebtForm
                userId={user.id}
                companyId={companyId}
                personId={personId}
                isPJ={isPJ}
                onSaved={loadDebts}
              />
            ) : null}
          </div>
        </div>
      </div>

      {selectedDebt ? (
        <DebtDetailPanel
          debt={selectedDebt}
          onClose={() => setSelectedDebt(null)}
          onActionTrigger={(type, debt) => {
            setSelectedDebt(null);
            setDialog({ type, debt });
          }}
        />
      ) : null}

      {dialog?.type === 'payment' ? <PaymentDialog debt={dialog.debt} onClose={() => setDialog(null)} onSuccess={handleActionSuccess} /> : null}
      {dialog?.type === 'agreement' && user?.id ? (
        <AgreementDialog debt={dialog.debt} userId={user.id} onClose={() => setDialog(null)} onSuccess={handleActionSuccess} />
      ) : null}
      {dialog?.type === 'abatement' ? <AbatementDialog debt={dialog.debt} onClose={() => setDialog(null)} onSuccess={handleActionSuccess} /> : null}
    </div>
  );
}
