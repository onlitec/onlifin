import * as React from 'react';
import Dashboard from './pages/Dashboard';
import Companies from './pages/Companies';
import Accounts from './pages/Accounts';
import Cards from './pages/Cards';
import Transactions from './pages/Transactions';
import Categories from './pages/Categories';
import Reports from './pages/Reports';
import Import from './pages/Import';
import ImportStatements from './pages/ImportStatements';
import Reconciliation from './pages/Reconciliation';
import Chat from './pages/Chat';
import ForecastDashboard from './pages/ForecastDashboard';
import BillsToPay from './pages/BillsToPay';
import BillsToReceive from './pages/BillsToReceive';

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
  {
    name: 'Dashboard',
    path: '/',
    element: <Dashboard />,
    visible: true
  },
  {
    name: 'Empresas',
    path: '/companies',
    element: <Companies />,
    visible: true
  },
  {
    name: 'Contas',
    path: '/accounts',
    element: <Accounts />,
    visible: true
  },
  {
    name: 'Cartões',
    path: '/cards',
    element: <Cards />,
    visible: true
  },
  {
    name: 'Transações',
    path: '/transactions',
    element: <Transactions />,
    visible: true,
    children: [
      {
        name: 'Contas a Pagar',
        path: '/bills-to-pay',
        element: <BillsToPay />,
        visible: true
      },
      {
        name: 'Contas a Receber',
        path: '/bills-to-receive',
        element: <BillsToReceive />,
        visible: true
      },
      {
        name: 'Importar Extrato',
        path: '/import-statements',
        element: <ImportStatements />,
        visible: true
      },
      {
        name: 'Importar',
        path: '/import',
        element: <Import />,
        visible: true
      },
      {
        name: 'Conciliação',
        path: '/reconciliation',
        element: <Reconciliation />,
        visible: true
      }
    ]
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
