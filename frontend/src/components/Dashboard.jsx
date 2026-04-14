import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getMe, logout, deactivateAccount } from '../api';

const Dashboard = () => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showConfirm, setShowConfirm] = useState(false);
  const [deactivating, setDeactivating] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchUser = async () => {
      try {
        const res = await getMe();
        if (res.success && res.user) {
          setUser(res.user);
        }
      } catch (err) {
        console.error('Failed to fetch user in Dashboard', err);
        navigate('/login', { replace: true });
      } finally {
        setLoading(false);
      }
    };
    fetchUser();
  }, [navigate]);

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login', { replace: true });
    } catch (err) {
      console.error('Logout failed', err);
    }
  };

  const handleDeactivate = async () => {
    setDeactivating(true);
    try {
      const res = await deactivateAccount();
      if (res.success) {
        // Account is gone — send them to login with no way back
        navigate('/login', { replace: true });
      } else {
        alert(res.message || 'Failed to deactivate account. Please try again.');
        setShowConfirm(false);
        setDeactivating(false);
      }
    } catch (err) {
      console.error('Deactivation failed', err);
      alert('An unexpected error occurred. Please try again.');
      setShowConfirm(false);
      setDeactivating(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-50">
        <div className="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">

      {/* ── Confirmation Modal ── */}
      {showConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 animate-fade-in">
            {/* Warning icon */}
            <div className="flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mx-auto mb-5">
              <svg className="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>

            <h3 className="text-xl font-bold text-gray-900 text-center mb-2">
              Deactivate Account?
            </h3>
            <p className="text-gray-500 text-sm text-center mb-8">
              This will permanently delete your account and all associated data.
              <span className="font-semibold text-red-600"> This action cannot be undone.</span>
            </p>

            <div className="flex gap-3">
              {/* Cancel — stays on dashboard */}
              <button
                onClick={() => setShowConfirm(false)}
                disabled={deactivating}
                className="flex-1 py-3 px-4 rounded-lg border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition cursor-pointer disabled:opacity-50"
              >
                No, Keep Account
              </button>

              {/* Confirm — deletes account */}
              <button
                onClick={handleDeactivate}
                disabled={deactivating}
                className="flex-1 py-3 px-4 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {deactivating ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    Deactivating...
                  </>
                ) : (
                  'Yes, Deactivate'
                )}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── Navbar ── */}
      <nav className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-bold text-indigo-600">My App</h1>
            </div>
            <div className="flex items-center">
              <button
                onClick={handleLogout}
                className="ml-4 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none transition-colors shadow-sm cursor-pointer"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* ── Main content ── */}
      <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
          <div className="p-8">
            <h2 className="text-3xl font-extrabold text-gray-900 mb-2">
              Welcome back, {user?.name || user?.full_name || user?.fullName || 'User'}!
            </h2>
            <p className="text-gray-500 text-lg mb-8">
              We're glad to see you again. Here is your dashboard summary.
            </p>

            {/* User info cards */}
            <div className="mt-6 border-t border-gray-100 pt-6">
              <dl className="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                <div className="sm:col-span-1 bg-indigo-50 rounded-lg p-5">
                  <dt className="text-sm font-medium text-indigo-500 truncate">Email Address</dt>
                  <dd className="mt-1 text-lg font-semibold text-gray-900 break-all">
                    {user?.email || 'N/A'}
                  </dd>
                </div>
                <div className="sm:col-span-1 bg-purple-50 rounded-lg p-5">
                  <dt className="text-sm font-medium text-purple-500 truncate">Account Status</dt>
                  <dd className="mt-1 text-lg font-semibold text-gray-900">
                    Active
                  </dd>
                </div>
              </dl>
            </div>

            {/* ── Danger Zone ── */}
            <div className="mt-10 border-t border-red-100 pt-8">
              <h3 className="text-sm font-semibold text-red-500 uppercase tracking-widest mb-3">
                Danger Zone
              </h3>
              <div className="flex items-center justify-between bg-red-50 border border-red-200 rounded-lg p-5">
                <div>
                  <p className="font-semibold text-gray-900">Deactivate Account</p>
                  <p className="text-sm text-gray-500 mt-1">
                    Permanently remove your account and all associated data. This cannot be undone.
                  </p>
                </div>
                <button
                  onClick={() => setShowConfirm(true)}
                  className="ml-6 flex-shrink-0 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition shadow cursor-pointer"
                >
                  Deactivate
                </button>
              </div>
            </div>

          </div>
        </div>
      </main>
    </div>
  );
};

export default Dashboard;
