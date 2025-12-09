import Dashboard from './pages/Dashboard';
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
import Admin from './pages/Admin';
import AIAdmin from './pages/AIAdmin';
import UserManagement from './pages/UserManagement';
import Login from './pages/Login';
import PWAInfo from './pages/PWAInfo';
import type { ReactNode } from 'react';

interface RouteConfig {
  name: string;
  path: string;
  element: ReactNode;
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
    element: <Admin />,
    visible: true,
    children: [
      {
        name: 'Painel Admin',
        path: '/admin',
        element: <Admin />,
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
        name: 'IA Admin',
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
  }
];

export default routes;
