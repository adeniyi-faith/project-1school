import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface UIState {
    sidebarOpen: boolean;
    sidebarCollapsed: boolean;
    mobileNavOpen: boolean;
    toggleSidebar: () => void;
    setSidebarOpen: (open: boolean) => void;
    toggleCollapsed: () => void;
    setMobileNavOpen: (open: boolean) => void;
}

export const useUIStore = create<UIState>()(
    persist(
        (set) => ({
            sidebarOpen: true,
            sidebarCollapsed: false,
            mobileNavOpen: false,
            toggleSidebar: () => set((s) => ({ sidebarOpen: !s.sidebarOpen })),
            setSidebarOpen: (open) => set({ sidebarOpen: open }),
            toggleCollapsed: () => set((s) => ({ sidebarCollapsed: !s.sidebarCollapsed })),
            setMobileNavOpen: (open) => set({ mobileNavOpen: open }),
        }),
        {
            name: 'ui-store',
            // The phone menu always starts closed.
            partialize: (state) => ({ sidebarOpen: state.sidebarOpen, sidebarCollapsed: state.sidebarCollapsed }),
        },
    ),
);
