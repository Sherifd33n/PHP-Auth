import { createContext, useContext, useState, useEffect } from 'react';
import { getMe } from '../api';

/**
 * AuthContext — stores the current user across the whole app.
 *
 * On first load, calls me.php to check whether a PHP session already exists
 * (e.g. the user refreshed the page). If the session is valid, the user is
 * restored automatically without needing to log in again.
 */
const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user,    setUser]    = useState(null);
  const [loading, setLoading] = useState(true);   // true while we check the session

  useEffect(() => {
    getMe()
      .then(data => setUser(data.user))
      .catch(()  => setUser(null))
      .finally(() => setLoading(false));
  }, []);

  return (
    <AuthContext.Provider value={{ user, setUser, loading }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
