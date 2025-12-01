import Dashboard from './pages/Dashboard';
import Accounts from './pages/Accounts';
import Transactions from './pages/Transactions';
import Admin from './pages/Admin';
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
    name: 'Transações',
    path: '/transactions',
    element: <Transactions />,
    visible: true
  },
  {
    name: 'Admin',
    path: '/admin',
    element: <Admin />,
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
