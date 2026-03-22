import { Navigate } from 'react-router-dom';
import * as React from 'react';
const Dashboard = React.lazy(() => import('./pages/Dashboard'));
const Companies = React.lazy(() => import('./pages/Companies'));
const Accounts = React.lazy(() => import('./pages/Accounts'));
const Cards = React.lazy(() => import('./pages/Cards'));
const Transactions = React.lazy(() => import('./pages/Transactions'));
const Categories = React.lazy(() => import('./pages/Categories'));
const Reports = React.lazy(() => import('./pages/Reports'));
const ImportStatements = React.lazy(() => import('./pages/ImportStatements'));
const Reconciliation = React.lazy(() => import('./pages/Reconciliation'));
const Chat = React.lazy(() => import('./pages/Chat'));
const ForecastDashboard = React.lazy(() => import('./pages/ForecastDashboard'));
const BillsToPay = React.lazy(() => import('./pages/BillsToPay'));
const BillsToReceive = React.lazy(() => import('./pages/BillsToReceive'));
const People = React.lazy(() => import('./pages/People'));
const Debts = React.lazy(() => import('./pages/Debts'));
const AIAdmin = React.lazy(() => import('./pages/AIAdmin'));
const AdminGeneral = React.lazy(() => import('./pages/AdminGeneral'));
const AdminNotifications = React.lazy(() => import('./pages/AdminNotifications'));
const UserManagement = React.lazy(() => import('./pages/UserManagement'));
const Login = React.lazy(() => import('./pages/Login'));
const ForceChangePassword = React.lazy(() => import('./pages/ForceChangePassword'));
const PWAInfo = React.lazy(() => import('./pages/PWAInfo'));
const SettingsPage = React.lazy(() => import('./pages/Settings'));
const UserPreferences = React.lazy(() => import('./pages/UserPreferences'));

interface RouteConfig {
  name: string;
  path: string;
  element?: React.ReactNode;
  visible?: boolean;
  children?: RouteConfig[];
}

const routes: RouteConfig[] = [
  // Redirecionamento inicial
  {
    name: 'Home',
    path: '/',
    element: <Navigate to="/pf" replace />,
    visible: false
  },

  // ===========================================
  // Módulo Pessoa Física (PF)
  // ===========================================
  {
    name: 'Pessoa Física',
    path: '/pf',
    visible: true,
    children: [
      { name: 'Dashboard PF', path: '/pf', element: <Dashboard />, visible: true },
      { name: 'Contas PF', path: '/pf/accounts', element: <Accounts />, visible: true },
      { name: 'Cartões PF', path: '/pf/cards', element: <Cards />, visible: true },
      { name: 'Transações PF', path: '/pf/transactions', element: <Transactions />, visible: true },
      { name: 'Contas a Pagar PF', path: '/pf/bills-to-pay', element: <BillsToPay />, visible: true },
      { name: 'Contas a Receber PF', path: '/pf/bills-to-receive', element: <BillsToReceive />, visible: true },
      { name: 'Importar Extrato PF', path: '/pf/import-statements', element: <ImportStatements />, visible: true },
      { name: 'Conciliação PF', path: '/pf/reconciliation', element: <Reconciliation />, visible: true },
      { name: 'Pessoas PF', path: '/pf/people', element: <People />, visible: true },
      { name: 'Dívidas PF', path: '/pf/debts', element: <Debts />, visible: true },
      { name: 'Relatórios PF', path: '/pf/reports', element: <Reports />, visible: true },
      { name: 'Previsão Financeira PF', path: '/pf/forecast', element: <ForecastDashboard />, visible: true },
    ]
  },

  // ===========================================
  // Módulo Pessoa Jurídica (PJ)
  // ===========================================
  {
    name: 'Pessoa Jurídica',
    path: '/pj/:companyId',
    visible: true,
    children: [
      { name: 'Dashboard PJ', path: '/pj/:companyId', element: <Dashboard />, visible: true },
      { name: 'Contas PJ', path: '/pj/:companyId/accounts', element: <Accounts />, visible: true },
      { name: 'Cartões PJ', path: '/pj/:companyId/cards', element: <Cards />, visible: true },
      { name: 'Transações PJ', path: '/pj/:companyId/transactions', element: <Transactions />, visible: true },
      { name: 'Contas a Pagar PJ', path: '/pj/:companyId/bills-to-pay', element: <BillsToPay />, visible: true },
      { name: 'Contas a Receber PJ', path: '/pj/:companyId/bills-to-receive', element: <BillsToReceive />, visible: true },
      { name: 'Importar Extrato PJ', path: '/pj/:companyId/import-statements', element: <ImportStatements />, visible: true },
      { name: 'Conciliação PJ', path: '/pj/:companyId/reconciliation', element: <Reconciliation />, visible: true },
      { name: 'Pessoas PJ', path: '/pj/:companyId/people', element: <People />, visible: true },
      { name: 'Dívidas PJ', path: '/pj/:companyId/debts', element: <Debts />, visible: true },
      { name: 'Relatórios PJ', path: '/pj/:companyId/reports', element: <Reports />, visible: true },
      { name: 'Previsão Financeira PJ', path: '/pj/:companyId/forecast', element: <ForecastDashboard />, visible: true },
    ]
  },

  // Rota de listagem de empresas (Gerenciamento)
  {
    name: 'Minhas Empresas',
    path: '/companies',
    element: <Companies />,
    visible: true
  },


  {
    name: 'PWA',
    path: '/pwa-info',
    element: <PWAInfo />,
    visible: false
  },
  {
    name: 'Preferências',
    path: '/preferences',
    element: <UserPreferences />,
    visible: true
  },
  {
    name: 'Admin',
    path: '/admin',
    element: <Navigate to="/admin-general" replace />,
    visible: false,
    children: [
      {
        name: 'Geral',
        path: '/admin-general',
        element: <AdminGeneral />,
        visible: true
      },
      {
        name: 'Categorias',
        path: '/categories',
        element: <Categories />,
        visible: true
      },
      {
        name: 'Notificações',
        path: '/admin-notifications',
        element: <AdminNotifications />,
        visible: true
      },
      {
        name: 'Assistente IA',
        path: '/chat',
        element: <Chat />,
        visible: true
      },
      {
        name: 'Gestão de Usuários',
        path: '/user-management',
        element: <UserManagement />,
        visible: true
      },
      {
        name: 'Configuração IA',
        path: '/ai-admin',
        element: <AIAdmin />,
        visible: true
      },
      {
        name: 'Backup e Restauro',
        path: '/settings',
        element: <SettingsPage />,
        visible: true
      }
    ]
  },
  {
    name: 'Login',
    path: '/login',
    element: <Login />,
    visible: false
  },
  {
    name: 'Alterar Senha',
    path: '/change-password',
    element: <ForceChangePassword />,
    visible: false
  }
];

export default routes;
