import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, RequireAuth } from 'miaoda-auth-react';
import { supabase } from '@/db/supabase';
import { Toaster } from '@/components/ui/toaster';
import Header from '@/components/common/Header';
import AIAssistant from '@/components/AIAssistant';
import routes from './routes';

function App() {
  return (
    <Router>
      <AuthProvider client={supabase}>
        <Toaster />
        <RequireAuth whiteList={['/login']}>
          <div className="flex flex-col min-h-screen">
            <Header />
            <main className="flex-grow bg-background">
              <Routes>
                {routes.map((route, index) => (
                  <Route
                    key={index}
                    path={route.path}
                    element={route.element}
                  />
                ))}
                <Route path="*" element={<Navigate to="/" replace />} />
              </Routes>
            </main>
            <AIAssistant />
          </div>
        </RequireAuth>
      </AuthProvider>
    </Router>
  );
}

export default App;
