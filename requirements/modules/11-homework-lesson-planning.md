# Module 11 — Homework & Lesson Planning

## Overview
Teacher-managed homework assignments with student submissions, lesson planning with principal review, syllabus tracking, and online class links.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Homework | Teacher assigns homework per class/subject with due date and attachment | File upload |
| Submission | Student submits homework file/text; teacher marks as done/reviewed | Status + file |
| Lesson Plan | Teacher creates weekly lesson plan per subject; principal reviews | Approval workflow |
| Syllabus | Upload syllabus PDF per class/subject; track completion % | Progress tracking |
| Online Classes | Embed Zoom / Google Meet / Jitsi link per period; admin configures default platform | URL field + embed; platform config in admin |

## Database Tables

### homework
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| class_id | FK | |
| subject_id | FK | |
| teacher_id | FK | Staff |
| title | string | |
| description | text | |
| due_date | date | |
| attachment | string | S3 path |

### homework_submissions
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| homework_id | FK | |
| student_id | FK | |
| file | string | S3 path |
| text_response | text | |
| status | enum | submitted / reviewed / returned |
| teacher_remarks | text | |

## Lesson Plan Workflow
1. Teacher creates lesson plan → status: `draft`
2. Principal reviews → status: `approved` / `rejected` with feedback
3. Approved plans visible to students/parents

## Syllabus Tracking
- Syllabus PDF uploaded per class/subject
- Teachers can mark topics as covered → completion % auto-calculated
- Parents/students can view syllabus and completion progress

## Sprint
**Phase 5 — Sprint 14**
- Homework assignment/submission, lesson plan, syllabus upload, online class link embed
