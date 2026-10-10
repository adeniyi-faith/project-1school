import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User, School } from '@/Types';

interface AuthState {
    user: User | null;
    school: School | null;
    permissions: string[];
    theme: 'light' | 'dark' | 'system';
    setUser: (user: User | null) => void;
    setSchool: (school: School | null) => void;
    setPermissions: (permissions: string[]) => void;
    setTheme: (theme: 'light' | 'dark' | 'system') => void;
    logout: () => void;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            user: null,
            school: null,
            permissions: [],
            theme: 'system',
            setUser: (user) => set({ user }),
            setSchool: (school) => set({ school }),
            setPermissions: (permissions) => set({ permissions }),
            setTheme: (theme) => set({ theme }),
            logout: () => set({ user: null, school: null, permissions: [] }),
        }),
        {
            name: 'auth-store',
            partialize: (state) => ({ theme: state.theme }),
        },
    ),
);
