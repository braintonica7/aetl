# Components to Remove - Analysis Report
**Date:** March 2, 2026  
**Purpose:** Identify components NOT required for Coaching Institute Management System

---

## Executive Summary

The current solution is built as an **Educational Quiz/Exam Platform with AI Analytics**. For the new **Coaching Institute Management System**, several components are either unnecessary or need to be replaced with coaching-specific features.

**Recommendation Categories:**
- 🔴 **REMOVE** - Not needed for coaching institute
- 🟡 **EVALUATE** - May be adapted or removed based on requirements
- 🟢 **KEEP** - Core functionality needed for coaching system

---

## 1. API CONTROLLERS TO REMOVE

### 🔴 **Wizi System (Complete Removal)**
**Files:**
- `api/application/controllers/api/Wizi_question.php`
- `api/application/controllers/api/Wizi_quiz.php`
- `api/application/controllers/api/Wizi_quiz_question.php`
- `api/application/controllers/api/Wizi_quiz_user.php`

**Reason:** 
- Wizi appears to be a separate quiz product/feature
- 386 lines in Wizi_question alone
- Not aligned with coaching institute requirements
- Has its own question bank, quiz system, and user tracking

**Impact:** Removes ~1500+ lines of code

---

### 🔴 **AI-Powered Features (Remove/Replace)**

#### Image Analysis
**File:** `api/application/controllers/api/Image_analysis.php` (423 lines)

**Reason:**
- AI-powered question extraction from images
- Expensive OpenAI API calls
- Not required for coaching institute administration
- Teachers will manually enter test questions

**Note:** If needed later for scanning test papers, can be re-implemented

#### Solution Generator
**File:** `api/application/controllers/api/Solution_generator.php` (if exists)

**Reason:**
- Automated solution generation not required
- Teachers will provide solutions manually
- AI costs not justified for coaching institute

---

### 🔴 **Quiz Builder**
**File:** `api/application/controllers/api/Quiz_builder.php` (373 lines)

**Reason:**
- Complex UUID-based quiz generation
- Time-based quiz calculations
- Over-engineered for coaching institute needs
- Will be replaced with simpler test series management

**Replacement:** Create `Test_series.php` for coaching tests

---

### 🟡 **Subscription System (Evaluate for Adaptation)**
**Files:**
- `api/application/controllers/api/Subscription.php`
- `api/application/controllers/api/Subscription_admin.php` (1323 lines)

**Reason:**
- Current system is for online subscription plans (monthly/academic year)
- Coaching needs installment-based fee management
- Razorpay integration exists but needs adaptation

**Recommendation:** 
- Remove if building fee management from scratch
- Keep and adapt if installment functionality can be added

---

### 🔴 **Question Analysis & Flagged Questions**
**Files:**
- `api/application/controllers/api/Question_analysis.php`
- `api/application/controllers/api/Flagged_questions.php`

**Reason:**
- Advanced analytics on question performance
- Quality control for question bank
- Not required for coaching institute tests
- Teachers manage test question quality directly

---

### 🟡 **User Performance & Points**
**Files:**
- `api/application/controllers/api/User_performance.php`
- `api/application/controllers/api/User_performance_summary.php`
- `api/application/controllers/api/User_points.php`

**Reason:**
- AI-powered performance analysis for online quizzes
- Gamification with points system
- May be excessive for coaching institute

**Recommendation:**
- Keep basic performance tracking
- Remove AI-powered deep analysis
- Remove points/gamification system
- Replace with simpler test result tracking

---

### 🔴 **Batch Notification Sender**
**File:** `api/application/controllers/api/Batch_notification_sender.php`

**Reason:**
- Appears to be for mass quiz notifications
- Coaching needs different notification types (fee reminders, attendance, test schedules)

**Replacement:** Keep basic notification system, rebuild for coaching-specific needs

---

### 🔴 **Quiz Scheduler**
**File:** `api/application/controllers/api/Quiz_schedule.php`
**File:** `api/application/controllers/api/Quiz_scholar.php`

**Reason:**
- Online quiz scheduling functionality
- Not relevant for in-person coaching tests

**Replacement:** Create `Test_schedule.php` for test timetables

---

### 🔴 **Payment Integration (Current)**
**File:** `api/application/controllers/api/Payment.php`

**Reason:**
- Integrated with subscription system
- May not support installment plans
- Needs rebuild for coaching fee structure

**Recommendation:** Rebuild from scratch for installment-based payments

---

### 🟡 **Client & Employee Management**
**Files:**
- `api/application/controllers/api/Client.php`
- `api/application/controllers/api/Employee.php`

**Reason:**
- Purpose unclear
- May be from original framework template
- Not standard for coaching institute

**Recommendation:** Review and remove if not being used

---

### 🔴 **Example & Test Controllers**
**Files:**
- `api/application/controllers/api/Example.php`
- `api/application/controllers/api/Test.php`

**Reason:**
- Demo/testing files
- Should be removed from production

---

### 🔴 **Keys & Generic API**
**Files:**
- `api/application/controllers/api/Key.php`

**Reason:**
- API key management seems unused
- JWT authentication is being used instead

---

## 2. ADMIN SCREENS TO REMOVE

### 🔴 **Wizi System Screens**
**Directories:**
- `admin/src/screens/wizi-quiz/`
  - WiziQuizList.tsx
  - WiziQuizCreate.tsx
  - WiziQuizEdit.tsx
- `admin/src/screens/wizi-question/`
- `admin/src/screens/wizi-quiz-question/`
- `admin/src/screens/wizi-quiz-user/`

**Reason:** Complete Wizi product removal

**Impact:** Removes 4 complete screen modules

---

### 🔴 **Solution Generator Screen**
**Directory:** `admin/src/screens/solution-generator/`

**Reason:**
- AI-powered solution generation
- Not needed for coaching institute
- Teachers provide solutions manually

---

### 🔴 **Flagged Questions Screen**
**Directory:** `admin/src/screens/flagged-questions/`
**Files:**
- FlaggedQuestionsList.tsx
- style.css

**Reason:**
- Question quality control system
- Not needed for coaching tests
- Teachers manage question quality directly

---

### 🔴 **Question Analysis Screen**
**Directory:** `admin/src/screens/question-analysis/`

**Reason:**
- Deep analytics on question performance
- Statistical analysis of question difficulty
- Overkill for coaching institute needs

---

### 🟡 **Subscription Screens (Evaluate)**
**Directories:**
- `admin/src/screens/subscription-plans/`
- `admin/src/screens/subscription-features/`
- `admin/src/screens/subscription-analytics/`
  - SubscriptionAnalytics.tsx
- `admin/src/screens/subscription-transactions/`
- `admin/src/screens/user-subscriptions/`
- `admin/src/screens/plan-features/`

**Reason:**
- 6 complete modules for subscription management
- Designed for monthly/yearly online subscriptions
- Coaching needs fee structures with installments

**Recommendation:**
- Remove all subscription screens
- Replace with fee management screens
- Reuse transaction components if applicable

---

### 🔴 **Batch Notification Sender Screen**
**Directory:** `admin/src/screens/batch-notification-sender/`

**Reason:**
- Specific to quiz notifications
- Rebuild for coaching-specific notifications (fee reminders, attendance alerts)

---

### 🟡 **Quiz Screens (Evaluate for Conversion)**
**Directory:** `admin/src/screens/quiz/`

**Reason:**
- Online quiz management
- May be convertible to test series management

**Recommendation:**
- Review components for reusability
- Remove online quiz-specific features
- Adapt for coaching test management

---

### 🟡 **Student Performance Screen**
**Directory:** `admin/src/screens/student-performance/`

**Reason:**
- Currently shows AI-analyzed quiz performance
- May be too complex for coaching needs

**Recommendation:**
- Simplify to show test results
- Remove AI analysis components
- Keep basic reporting

---

### 🔴 **Notification Context & Preview**
**Directories:**
- `admin/src/screens/notification-context/`
- `admin/src/screens/notification-preview/`

**Reason:**
- Complex notification template system for quiz notifications
- Coaching needs simpler, direct notifications

**Recommendation:** Simplify notification system

---

## 3. BACKEND MODELS TO REMOVE

### 🔴 **Wizi Models (Complete)**
**Directories:**
- `api/application/models/wizi_quiz/`
  - Wizi_question_model.php
  - Wizi_question_object.php
  - Wizi_quiz_model.php
  - Wizi_quiz_object.php
  - Wizi_quiz_question_model.php
  - Wizi_quiz_question_object.php
  - Wizi_quiz_question_user_model.php
  - Wizi_quiz_question_user_object.php
  - Wizi_quiz_user_model.php

**Reason:** Complete removal of Wizi system

---

### 🔴 **Video/Content Management**
**Directories:**
- `api/application/models/video/`
  - video_model.php
  - video_object.php
- `api/application/models/content/`
- `api/application/models/content_query/`
- `api/application/models/content_read_status/`
- `api/application/models/content_section/`
- `api/application/models/content_type/`

**Reason:**
- LMS (Learning Management System) features
- Video lessons management
- Content delivery system
- Not required for coaching institute focused on in-person teaching

**Impact:** Removes entire content management system

---

### 🔴 **Discussion Forums**
**Directories:**
- `api/application/models/discussion/`
- `api/application/models/discussion_scholar/`
- `api/application/models/student_discussion/`

**Reason:**
- Online discussion forums
- Not needed for in-person coaching
- Can be replaced with simple Q&A if needed

---

### 🔴 **Meeting Management**
**Directories:**
- `api/application/models/meeting_category/`
- `api/application/models/meeting_log/`
- `api/application/models/meeting_participant/`
- `api/application/models/meeting_schedule/`

**Reason:**
- Online meeting/virtual class scheduling
- Not required for physical coaching centers
- Excessive for coaching needs

---

### 🔴 **Time-Limited Assignments**
**Directories:**
- `api/application/models/time_limit_assignment/`
- `api/application/models/time_limit_assignment_check/`
- `api/application/models/time_limit_assignment_submission/`

**Reason:**
- Online homework submission system
- Time-bound assignment features
- Not required for coaching institute

---

### 🔴 **RFID/Scanning System**
**Directories:**
- `api/application/models/rfidlog/`
- `api/application/models/scan/`

**Reason:**
- RFID-based attendance or access control
- Card scanning features
- Unless specifically needed, remove

**Note:** If implementing automated attendance, evaluate for reuse

---

### 🔴 **Problem Management**
**Directory:** `api/application/models/problem/`

**Reason:**
- Purpose unclear
- Likely related to online platform issue tracking
- Not standard for coaching institute

---

### 🟡 **Teacher Management (Evaluate)**
**Directories:**
- `api/application/models/teacher_class_section/`
- `api/application/models/teacher_subject/`

**Reason:**
- May be useful for teacher-batch assignments
- May be unnecessary if simpler structure works

**Recommendation:** Evaluate based on requirement for teacher management

---

### 🟡 **Class Schedule & Time Slots**
**Directories:**
- `api/application/models/class_schedule/`
- `api/application/models/time_slots/`

**Reason:**
- May be useful for batch timetable management
- Could be overkill if classes are fixed

**Recommendation:** Evaluate based on scheduling requirements

---

### 🟡 **Subscription Models (Evaluate)**
**Directory:** `api/application/models/subscription/`
**Files:**
- Subscription_plan_model.php
- Subscription_plan_object.php
- Subscription_transaction_model.php
- Subscription_transaction_object.php
- User_subscription_model.php
- User_subscription_object.php
- Subscription_feature_definition_object.php
- Subscription_payment_method_object.php
- Subscription_usage_tracking_object.php

**Reason:**
- Designed for recurring online subscriptions
- Not applicable to coaching fee structure

**Recommendation:** Remove and rebuild as fee management system

---

### 🟡 **User Performance Models (Simplify)**
**Directories:**
- `api/application/models/user_performance/`
  - User_performance_model.php
  - Badge_model.php
  - Student_report_card_model.php
- `api/application/models/user_performance_summary/`
- `api/application/models/user_point/`

**Reason:**
- AI-powered performance analysis
- Gamification features (badges, points)
- Over-engineered for coaching needs

**Recommendation:**
- Keep basic report card functionality
- Remove AI analysis, badges, and points
- Simplify to test result tracking

---

## 4. COMPONENTS TO KEEP & ENHANCE

### 🟢 **Core Components**
- **User Management** (`api/User.php`, `admin/users/`) - **KEEP & ENHANCE**
  - Add student-specific fields
  - Add parent contact information
  
- **Subject/Chapter/Topic** - **KEEP**
  - Core academic structure needed
  
- **Question Bank** - **KEEP & SIMPLIFY**
  - Remove advanced analytics
  - Keep basic question management
  
- **Exam/Quiz Base** - **ADAPT**
  - Convert to test series management
  - Simplify for in-person tests
  
- **Scholar/Section Models** - **KEEP & ENHANCE**
  - Already has basic student structure
  - Enhance with coaching-specific fields
  
- **Notification System** - **KEEP & REBUILD**
  - Adapt for coaching notifications
  
- **Reports** - **KEEP & SIMPLIFY**
  - Remove AI analytics
  - Focus on test results and attendance
  
- **Authentication** (JWT) - **KEEP**
  - Working auth system
  
- **Role Management** - **KEEP**
  - Already has role-based access

---

## 5. ESTIMATED CODE REDUCTION

### API Controllers
- **Remove:** ~15-20 files
- **Lines Saved:** ~5,000-8,000 lines

### Admin Screens
- **Remove:** ~15-20 screen modules
- **Components Saved:** ~50-100 React components

### Backend Models
- **Remove:** ~25-35 model directories
- **Files Saved:** ~100+ model and object files

### Overall Impact
- **Total Reduction:** 40-50% of current codebase
- **Maintenance:** Significantly reduced complexity
- **Focus:** Clear coaching institute functionality

---

## 6. MIGRATION STRATEGY

### Phase 1: Backup & Branch
1. Create backup of current system
2. Create new branch: `coaching-system-cleanup`
3. Document all removed components

### Phase 2: Safe Removal (Week 1)
1. Remove Wizi system completely
2. Remove video/content management
3. Remove discussion forums
4. Remove meeting management
5. Remove time-limited assignments

### Phase 3: Evaluation (Week 2)
1. Review subscription system for adaptation
2. Evaluate user performance for simplification
3. Review teacher management needs
4. Assess scheduling requirements

### Phase 4: Replacement (Week 3-4)
1. Build fee management system
2. Simplify test series management
3. Rebuild notification system for coaching
4. Create attendance system

### Phase 5: Testing & Cleanup (Week 5)
1. Test remaining functionality
2. Remove unused database tables
3. Clean up navigation menus
4. Update documentation

---

## 7. RISK ASSESSMENT

### Low Risk Removals
- Wizi system (isolated)
- Video/content management (isolated)
- Discussion forums (isolated)
- Meeting management (isolated)
- Time-limited assignments (isolated)
- Example/test controllers (isolated)

### Medium Risk Removals
- Subscription system (deeply integrated)
- Quiz builder (may affect question management)
- Performance analytics (interconnected)

### High Risk Removals
- Base quiz/exam system (foundational)
  - **Recommendation:** Adapt, don't remove

---

## 8. DATABASE CLEANUP

### Tables to Drop (Estimated)
```sql
-- Wizi system
- wizi_quiz
- wizi_question
- wizi_quiz_question
- wizi_quiz_user
- wizi_quiz_question_user

-- Content management
- video
- content
- content_query
- content_section
- content_type
- content_read_status

-- Collaboration
- discussion
- discussion_scholar
- student_discussion
- meeting_category
- meeting_log
- meeting_participant
- meeting_schedule

-- Assignments
- time_limit_assignment
- time_limit_assignment_check
- time_limit_assignment_submission

-- Tracking
- rfidlog
- scan

-- Subscriptions (if replacing)
- subscription_plans
- subscription_features
- user_subscriptions
- subscription_transactions
- subscription_usage_tracking
```

**Estimated:** 30-40 tables can be removed

---

## 9. DEPENDENCIES TO CHECK

Before removing any component, verify:
1. Database foreign key constraints
2. Shared libraries/helpers
3. Common utilities
4. Authentication dependencies
5. Menu/navigation references
6. API endpoint consumers

---

## 10. RECOMMENDATIONS

### Immediate Actions
1. ✅ Remove Wizi system completely
2. ✅ Remove video/content management
3. ✅ Remove discussion and meeting features
4. ✅ Remove time-limited assignments
5. ✅ Remove RFID/scanning features
6. ✅ Remove demo/test controllers

### Evaluate & Decide
1. ⚠️ Subscription system - Remove or adapt?
2. ⚠️ Quiz builder - Simplify or replace?
3. ⚠️ Performance analytics - Remove AI, keep basic?
4. ⚠️ Points/badges - Remove gamification?
5. ⚠️ Teacher management - Needed or not?

### Keep & Enhance
1. 🔄 Core user management
2. 🔄 Subject/chapter/topic structure
3. 🔄 Basic question management
4. 🔄 Scholar/section models
5. 🔄 Authentication system
6. 🔄 Role-based access

---

## 11. NEXT STEPS

1. **Review this analysis** with stakeholders
2. **Prioritize removals** based on business needs
3. **Create detailed removal plan** for each component
4. **Set up version control** strategy
5. **Begin with low-risk removals** first
6. **Document all changes** for future reference
7. **Test thoroughly** after each removal phase

---

## Conclusion

**Estimated Cleanup Impact:**
- **40-50% code reduction**
- **Simplified architecture**
- **Focused coaching functionality**
- **Reduced maintenance overhead**
- **Clearer development path**

This cleanup will transform the platform from a comprehensive online education platform to a focused coaching institute management system, making it easier to maintain and extend with coaching-specific features.

---

**Document Version:** 1.0  
**Last Updated:** March 2, 2026  
**Status:** READY FOR REVIEW
