import Dashboard from './pages/Dashboard';
import Accounts from './pages/Accounts';
import Cards from './pages/Cards';
import Transactions from './pages/Transactions';
import Categories from './pages/Categories';
import Reports from './pages/Reports';
import Import from './pages/Import';
import Reconciliation from './pages/Reconciliation';
import Admin from './pages/Admin';
import AIAdmin from './pages/AIAdmin';
import Login from './pages/Login';
import type { ReactNode } from 'react';

interface RouteConfig {
  name: string;
  path: string;
  element: ReactNode;
  visible?: boolean;
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
    visible: true
  },
  {
    name: 'Categorias',
    path: '/categories',
    element: <Categories />,
    visible: true
  },
  {
    name: 'Relatórios',
    path: '/reports',
    element: <Reports />,
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
  },
  {
    name: 'Admin',
    path: '/admin',
    element: <Admin />,
    visible: false
  },
  {
    name: 'IA Admin',
    path: '/ai-admin',
    element: <AIAdmin />,
    visible: false
  },
  {
    name: 'Login',
    path: '/login',
    element: <Login />,
    visible: false
  }
];

export default routes;
