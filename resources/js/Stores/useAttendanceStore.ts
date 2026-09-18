import { create } from 'zustand';

type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

interface AttendanceRecord {
    status: AttendanceStatus;
    remarks: string;
}

interface AttendanceState {
    currentDate: string;
    currentClassId: string;
    currentSectionId: string;
    records: Record<number, AttendanceRecord>;

    setDate: (date: string) => void;
    setClassId: (classId: string) => void;
    setSectionId: (sectionId: string) => void;
    markStudent: (studentId: number, status: AttendanceStatus, remarks?: string) => void;
    markAll: (studentIds: number[], status: AttendanceStatus) => void;
    initRecords: (existing: Record<number, { status: AttendanceStatus; remarks: string | null }>, studentIds: number[]) => void;
    reset: () => void;
}

export const useAttendanceStore = create<AttendanceState>((set) => ({
    currentDate:      new Date().toISOString().slice(0, 10),
    currentClassId:   '',
    currentSectionId: '',
    records:          {},

    setDate:      (date)      => set({ currentDate: date }),
    setClassId:   (classId)   => set({ currentClassId: classId, records: {} }),
    setSectionId: (sectionId) => set({ currentSectionId: sectionId, records: {} }),

    markStudent: (studentId, status, remarks = '') =>
        set((state) => ({
            records: {
                ...state.records,
                [studentId]: { status, remarks },
            },
        })),

    markAll: (studentIds, status) =>
        set((state) => {
            const updated = { ...state.records };
            studentIds.forEach((id) => {
                updated[id] = { status, remarks: updated[id]?.remarks ?? '' };
            });
            return { records: updated };
        }),

    initRecords: (existing, studentIds) =>
        set(() => {
            const records: Record<number, AttendanceRecord> = {};
            studentIds.forEach((id) => {
                records[id] = {
                    status:  existing[id]?.status  ?? 'present',
                    remarks: existing[id]?.remarks ?? '',
                };
            });
            return { records };
        }),

    reset: () => set({ records: {}, currentClassId: '', currentSectionId: '' }),
}));
