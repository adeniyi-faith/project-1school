# Module 06 — Timetable & Scheduling

## Overview
Drag-and-drop timetable builder with conflict detection, teacher schedule views, room management, exam scheduling, and calendar export.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Timetable Builder | Drag-and-drop weekly timetable per class/section; conflict detection | React DnD, grid layout |
| Teacher Schedule | Auto-generated view of a teacher's weekly periods across classes | Computed from timetable |
| Room Management | Assign classrooms; detect double-booking conflicts | Constraint check |
| Exam Schedule | Separate exam timetable with hall allocation, invigilator assignment | Extends timetable model |
| iCal Export | Export personal schedule to Google/Apple Calendar | iCal file generation |

## Database Table

### timetables
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| class_id | FK | |
| section_id | FK | |
| subject_id | FK | |
| teacher_id | FK | Staff |
| day_of_week | enum | Mon-Sun |
| start_time | time | |
| end_time | time | |
| room | string | Room/hall identifier |
| UNIQUE | | Constraint on overlap per room+day+time |

## Conflict Detection Rules
1. Same teacher cannot be in two classes at the same time
2. Same room cannot have two classes at the same time
3. Same class/section cannot have two subjects at the same time
- All constraints enforced at DB level (unique constraint) and validated in Service layer

## Sprint
**Phase 3 — Sprint 7**
- Timetable builder (drag-and-drop), conflict detection, exam schedule, room management, iCal export
