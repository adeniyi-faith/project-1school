import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface UIState {
    sidebarOpen: boolean;
    sidebarCollapsed: boolean;
    mobileNavOpen: boolean;
    /** Menu groups the person opened or closed by hand (true = open). Groups not listed use their default. */
    groupOpen: Record<string, boolean>;
    setGroupOpen: (title: string, open: boolean) => void;
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
            groupOpen: {},
            setGroupOpen: (title, open) => set((s) => ({ groupOpen: { ...s.groupOpen, [title]: open } })),
            toggleSidebar: () => set((s) => ({ sidebarOpen: !s.sidebarOpen })),
            setSidebarOpen: (open) => set({ sidebarOpen: open }),
            toggleCollapsed: () => set((s) => ({ sidebarCollapsed: !s.sidebarCollapsed })),
            setMobileNavOpen: (open) => set({ mobileNavOpen: open }),
        }),
        {
            name: 'ui-store',
            // The phone menu always starts closed.
            partialize: (state) => ({ sidebarOpen: state.sidebarOpen, sidebarCollapsed: state.sidebarCollapsed, groupOpen: state.groupOpen }),
        },
    ),
);
