import { Navigate } from 'react-router-dom';
import * as React from 'react';
import Dashboard from './pages/Dashboard';
import Companies from './pages/Companies';
import Accounts from './pages/Accounts';
import Cards from './pages/Cards';
import Transactions from './pages/Transactions';
import Categories from './pages/Categories';
import Reports from './pages/Reports';
import ImportStatements from './pages/ImportStatements';
import Reconciliation from './pages/Reconciliation';
import Chat from './pages/Chat';
import ForecastDashboard from './pages/ForecastDashboard';
import BillsToPay from './pages/BillsToPay';
import BillsToReceive from './pages/BillsToReceive';
import People from './pages/People';


import AIAdmin from './pages/AIAdmin';
import AdminGeneral from './pages/AdminGeneral';
import UserManagement from './pages/UserManagement';
import Login from './pages/Login';
import ForceChangePassword from './pages/ForceChangePassword';
import PWAInfo from './pages/PWAInfo';

interface RouteConfig {
  name: string;
  path: string;
  element: React.ReactNode;
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
    element: <Dashboard />,
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
    ]
  },

  // ===========================================
  // Módulo Pessoa Jurídica (PJ)
  // ===========================================
  {
    name: 'Pessoa Jurídica',
    path: '/pj/:companyId',
    element: <Dashboard />,
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
    name: 'Relatórios',
    path: '/reports',
    element: <Reports />,
    visible: true
  },
  {
    name: 'Previsão Financeira',
    path: '/forecast',
    element: <ForecastDashboard />,
    visible: true
  },
  {
    name: 'PWA',
    path: '/pwa-info',
    element: <PWAInfo />,
    visible: false
  },
  {
    name: 'Admin',
    path: '/admin',
    element: <UserManagement />,
    visible: false, // Hidden from navigation - rendered manually in Header for admin users
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
