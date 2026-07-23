<?php

namespace Database\Seeders;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrmsGuideSeeder extends Seeder
{
    public function run(): void
    {
        // Remove any previously seeded guide modules so this is idempotent
        TrainingModule::whereNotNull('created_by')->delete();

        $admin   = User::where('role', 'admin')->first();
        $adminId = $admin?->id ?? 1;

        $guide = [

            // ── 1. Getting Started ──────────────────────────────────────────
            [
                'title'       => 'Getting Started',
                'description' => 'Login, Dashboard overview, and navigating the application.',
                'lessons' => [
                    [
                        'title'   => 'Logging In',
                        'content' => '<h4>How to Log In</h4>
<p>Open the application URL in your browser. You will be presented with the login screen.</p>
<ol>
  <li>Enter your <strong>Email Address</strong> and <strong>Password</strong> in the fields provided.</li>
  <li>Click <strong>Login</strong>.</li>
  <li>If your organisation uses Single Sign-On (SSO), click the <strong>Login with SSO</strong> button instead.</li>
</ol>
<p>On a successful login you will be redirected to the <strong>Dashboard</strong>.</p>
<hr>
<h5>Forgot Password?</h5>
<p>Contact your system administrator to reset your password. Only admins can manage user accounts.</p>',
                    ],
                    [
                        'title'   => 'Dashboard Overview',
                        'content' => '<h4>Dashboard</h4>
<p>The Dashboard is your home screen. It shows a snapshot of key HR metrics at a glance:</p>
<ul>
  <li><strong>Total Employees</strong> – count of all active staff.</li>
  <li><strong>Pending Leave Requests</strong> – leave requests awaiting approval.</li>
  <li><strong>Today\'s Attendance</strong> – employees marked present today.</li>
  <li><strong>Upcoming Holidays</strong> – next public or company holidays.</li>
</ul>
<p>Use the cards as quick links — clicking a card navigates directly to that module.</p>',
                    ],
                    [
                        'title'   => 'Navigation & Sidebar',
                        'content' => '<h4>Sidebar Navigation</h4>
<p>The left sidebar organises all modules into sections. Click any item to navigate to it. On small screens tap the hamburger (☰) icon at the top to show/hide the sidebar.</p>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
  <thead><tr style="background:#f0f4ff"><th>Section</th><th>Modules</th></tr></thead>
  <tbody>
    <tr><td>People</td><td>Employees, Departments, Designations</td></tr>
    <tr><td>Attendance</td><td>Attendance, On-Duty, Holidays, Comp-Off</td></tr>
    <tr><td>Leave</td><td>Leave Requests, Leave History, Leave Status</td></tr>
    <tr><td>Payroll</td><td>Salary Slips, Salary Components, Benefits, Bonuses, Loans</td></tr>
    <tr><td>Letters</td><td>Offer Letters, Confirmation Letters, Increment Letters</td></tr>
    <tr><td>Reports</td><td>Attendance Report, Benefit Reports</td></tr>
    <tr><td>Settings</td><td>Entities, Users, Roles, OT Settings, Grace Settings</td></tr>
  </tbody>
</table>',
                    ],
                ],
            ],

            // ── 2. Employee Management ───────────────────────────────────────
            [
                'title'       => 'Employee Management',
                'description' => 'Add, edit, and manage employee profiles, documents, and bank details.',
                'lessons' => [
                    [
                        'title'   => 'Adding a New Employee',
                        'content' => '<h4>Create Employee Profile</h4>
<ol>
  <li>Go to <strong>Employees</strong> in the sidebar and click <strong>Add Employee</strong>.</li>
  <li>Fill in the required fields:
    <ul>
      <li><strong>Full Name</strong>, <strong>Email</strong>, <strong>Phone</strong></li>
      <li><strong>Employee Code</strong> – unique identifier (e.g. EMP001)</li>
      <li><strong>Department</strong> and <strong>Designation</strong></li>
      <li><strong>Joining Date</strong> and <strong>Entity</strong> (the company the employee belongs to)</li>
      <li><strong>Fixed Salary</strong> – monthly CTC base amount</li>
    </ul>
  </li>
  <li>Optionally upload a <strong>Photo</strong> and set <strong>OT Enabled</strong> if the employee is eligible for overtime pay.</li>
  <li>Click <strong>Save Employee</strong>.</li>
</ol>
<p><strong>Tip:</strong> You can also bulk-import employees via an Excel file. Click <strong>Import</strong> on the Employees list page and download the template first.</p>',
                    ],
                    [
                        'title'   => 'Editing Employee Details',
                        'content' => '<h4>Edit an Employee</h4>
<ol>
  <li>On the Employees list, click the <strong>Edit</strong> (pencil) icon on the employee\'s row.</li>
  <li>Update any field as needed.</li>
  <li>Click <strong>Update Employee</strong> to save.</li>
</ol>
<h5>Salary Status</h5>
<p>Click the <strong>Salary Status</strong> button on an employee\'s row to see a month-by-month overview of generated salary slips for that employee.</p>',
                    ],
                    [
                        'title'   => 'Employee Documents',
                        'content' => '<h4>Uploading & Viewing Documents</h4>
<p>Go to <strong>Employee Documents</strong> in the sidebar.</p>
<ol>
  <li>Select the <strong>Employee</strong> and <strong>Document Type</strong> (e.g. Aadhaar, PAN, Resume).</li>
  <li>Choose the file and click <strong>Upload Document</strong>.</li>
  <li>To view a document, click <strong>Preview</strong>; to save it click <strong>Download</strong>.</li>
  <li>To remove a document, click the <strong>Delete</strong> (trash) icon.</li>
</ol>',
                    ],
                    [
                        'title'   => 'Bank Details',
                        'content' => '<h4>Adding Bank Details</h4>
<p>Bank details are managed from the employee\'s profile (Admin / Manager access only).</p>
<ol>
  <li>Open the employee\'s profile and click <strong>Bank Details</strong>.</li>
  <li>Enter the <strong>Bank Name</strong>, <strong>Account Number</strong>, <strong>IFSC Code</strong>, and <strong>Branch</strong>.</li>
  <li>Click <strong>Save Bank Details</strong>.</li>
</ol>',
                    ],
                    [
                        'title'   => 'Family Members',
                        'content' => '<h4>Adding Family Members</h4>
<p>Family member records are used for insurance and nominee purposes.</p>
<ol>
  <li>Open the employee\'s profile and scroll to the <strong>Family Members</strong> section.</li>
  <li>Click <strong>Add Family Member</strong>, fill in Name, Relationship, Date of Birth, and Contact details.</li>
  <li>Click <strong>Save</strong>.</li>
</ol>',
                    ],
                ],
            ],

            // ── 3. Attendance Management ─────────────────────────────────────
            [
                'title'       => 'Attendance Management',
                'description' => 'Record daily attendance, import biometric data, mark on-duty, and view reports.',
                'lessons' => [
                    [
                        'title'   => 'Manual Attendance Entry',
                        'content' => '<h4>Recording Attendance</h4>
<ol>
  <li>Go to <strong>Attendance</strong> in the sidebar.</li>
  <li>Select the <strong>Employee</strong>, <strong>Date</strong>, <strong>Check-In</strong> time, and <strong>Check-Out</strong> time.</li>
  <li>Set <strong>Status</strong>: Present, Late, Half Day, Absent, or On Leave.</li>
  <li>Click <strong>Save Attendance</strong>.</li>
</ol>
<p>The system automatically calculates <strong>Working Hours</strong> and <strong>Overtime</strong> based on the configured OT settings.</p>',
                    ],
                    [
                        'title'   => 'Importing Attendance (Biometric / Excel)',
                        'content' => '<h4>Bulk Import</h4>
<p>Two import options are available on the Attendance page:</p>
<h5>Daily Import</h5>
<ol>
  <li>Click <strong>Import Attendance</strong>.</li>
  <li>Upload your biometric export file (Excel/CSV with columns: Employee Code, Date, Check-In, Check-Out).</li>
  <li>Click <strong>Import</strong>. Errors are shown row-by-row after upload.</li>
</ol>
<h5>Monthly Import</h5>
<ol>
  <li>Click <strong>Monthly Import</strong>.</li>
  <li>Select <strong>Month</strong> and <strong>Year</strong>.</li>
  <li>Upload the monthly consolidated Excel file.</li>
  <li>Click <strong>Import</strong>.</li>
</ol>
<p><strong>Note:</strong> Importing will overwrite existing records for the same employee and date.</p>',
                    ],
                    [
                        'title'   => 'Attendance Report',
                        'content' => '<h4>Viewing the Monthly Report</h4>
<ol>
  <li>Go to <strong>Attendance → Report</strong>.</li>
  <li>Choose the <strong>Month</strong> and <strong>Year</strong> and optionally filter by <strong>Department</strong> or <strong>Employee</strong>.</li>
  <li>Click <strong>Generate Report</strong>.</li>
</ol>
<h5>Columns Explained</h5>
<ul>
  <li><strong>Days Present</strong> – total days with status Present or Late.</li>
  <li><strong>Paid Days</strong> – days counted for salary calculation (deducting LOP).</li>
  <li><strong>Late</strong> – days where check-in was after grace time.</li>
  <li><strong>Absent / LOP</strong> – loss-of-pay days deducted from salary.</li>
  <li><strong>OT Hrs</strong> – total overtime hours accumulated.</li>
  <li><strong>Man Hrs</strong> – total logged working hours for the month.</li>
</ul>',
                    ],
                    [
                        'title'   => 'On-Duty Requests',
                        'content' => '<h4>Marking On-Duty</h4>
<p>On-Duty is used when an employee works outside the office (site visit, client meeting, etc.) and the attendance won\'t appear in biometric data.</p>
<ol>
  <li>Go to <strong>On-Duty</strong> in the sidebar.</li>
  <li>Select <strong>Employee</strong>, <strong>Date</strong>, and <strong>Reason</strong>.</li>
  <li>Click <strong>Mark On-Duty</strong>.</li>
</ol>
<p>On-Duty records are treated as Present during salary calculation.</p>',
                    ],
                ],
            ],

            // ── 4. Holiday & Comp-Off ────────────────────────────────────────
            [
                'title'       => 'Holidays & Comp-Off',
                'description' => 'Manage the holiday calendar, mark working days, and handle comp-off balances.',
                'lessons' => [
                    [
                        'title'   => 'Adding & Managing Holidays',
                        'content' => '<h4>Holiday Calendar</h4>
<ol>
  <li>Go to <strong>Holidays</strong> in the sidebar.</li>
  <li>The calendar displays the entire year. Sundays and 1st/3rd Saturdays are shown as Weekly Off automatically.</li>
  <li>To add a holiday, fill in <strong>Date</strong>, <strong>Holiday Name</strong>, and optionally a <strong>Holiday Type</strong>, then click <strong>Add Holiday</strong>.</li>
  <li>To remove a holiday, click the <strong>Delete</strong> icon in the Actions column.</li>
</ol>
<h5>Importing Holidays</h5>
<p>Click <strong>Import Holidays</strong>, upload an Excel/CSV with columns Date and Name, select a type, and click Import.</p>',
                    ],
                    [
                        'title'   => 'Marking a Holiday as Working Day',
                        'content' => '<h4>Working Day Toggle</h4>
<p>When the company requires employees to work on a public holiday or weekly off:</p>
<ol>
  <li>Click the <strong>Mark as Working Day</strong> button on that day\'s row.</li>
  <li>A dialog will appear asking for:
    <ul>
      <li><strong>Entity</strong> – the company issuing the circular.</li>
      <li><strong>Reason</strong> – why this day is being made a working day.</li>
    </ul>
  </li>
  <li>Click <strong>Confirm</strong>. The system automatically creates a <strong>Comp-Off</strong> record for every active employee.</li>
  <li>A <strong>Download Circular</strong> button appears — click it to generate a PDF circular with an employee acknowledgement signature table.</li>
</ol>
<p>To <strong>undo</strong>, click <strong>Restore as Off Day</strong> — unvailed comp-off records will be removed.</p>',
                    ],
                    [
                        'title'   => 'Comp-Off Management',
                        'content' => '<h4>Compensatory Off</h4>
<p>Go to <strong>Comp-Off</strong> in the sidebar to see all comp-off entitlements.</p>
<h5>Availing a Comp-Off</h5>
<ol>
  <li>Select the records you want to avail (tick the checkboxes).</li>
  <li>Click <strong>Avail Selected</strong>.</li>
  <li>The status changes from <em>Pending</em> to <em>Availed</em>.</li>
</ol>
<h5>Adding Manual Comp-Off</h5>
<ol>
  <li>Click <strong>Add Comp-Off</strong>.</li>
  <li>Select Employee, Holiday Date, and Holiday Name.</li>
  <li>Click <strong>Save</strong>.</li>
</ol>',
                    ],
                ],
            ],

            // ── 5. Leave Management ──────────────────────────────────────────
            [
                'title'       => 'Leave Management',
                'description' => 'Submit, approve, reject, and track leave requests across the organisation.',
                'lessons' => [
                    [
                        'title'   => 'Creating a Leave Request',
                        'content' => '<h4>New Leave Request</h4>
<ol>
  <li>Go to <strong>Leave Requests</strong> and click <strong>New Request</strong>.</li>
  <li>Select the <strong>Employee</strong>, <strong>Leave Type</strong>, <strong>From Date</strong>, and <strong>To Date</strong>.</li>
  <li>Enter a <strong>Reason</strong> for the leave.</li>
  <li>Optionally attach a <strong>Supporting Document</strong> (PDF, JPG, PNG, DOC, DOCX – max 5 MB).</li>
  <li>Click <strong>Submit Leave Request</strong>.</li>
</ol>
<p>The number of leave days is calculated automatically, excluding Sundays, 1st/3rd Saturdays, and other holidays.</p>',
                    ],
                    [
                        'title'   => 'Approving or Rejecting Leaves',
                        'content' => '<h4>Manage Leave Requests</h4>
<ol>
  <li>Go to <strong>Leave Requests</strong>. Pending requests are shown at the top.</li>
  <li>Click <strong>View</strong> on a request to see full details including any attached document.</li>
  <li>Click <strong>Approve</strong> to approve or <strong>Reject</strong> to reject.</li>
  <li>Optionally enter a remark before rejecting.</li>
</ol>
<p>Approved leaves are automatically counted in payroll (LOP deduction applies for unpaid leave types).</p>',
                    ],
                    [
                        'title'   => 'Leave History',
                        'content' => '<h4>Complete Leave History</h4>
<p>Go to <strong>Leave History</strong> in the sidebar to see a historical view of all leave requests.</p>
<h5>Filters Available</h5>
<ul>
  <li><strong>Employee</strong> – filter by a specific employee</li>
  <li><strong>Leave Type</strong> – e.g. Casual Leave, Sick Leave</li>
  <li><strong>Status Tab</strong> – All / Pending / Approved / Rejected</li>
  <li><strong>Year</strong> and <strong>Month</strong></li>
</ul>
<p>The summary cards at the top show total, approved, pending, and rejected counts for the applied filter.</p>',
                    ],
                    [
                        'title'   => 'Leave Status (Balance Grid)',
                        'content' => '<h4>Leave Balance Overview</h4>
<p>Go to <strong>Leave Status</strong> to see a yearly grid showing each employee\'s leave balance per leave type.</p>
<ul>
  <li>Columns show <strong>Allocated</strong>, <strong>Used</strong>, and <strong>Balance</strong> days per leave type.</li>
  <li>Click <strong>Export</strong> to download the grid as Excel.</li>
</ul>',
                    ],
                ],
            ],

            // ── 6. Payroll ───────────────────────────────────────────────────
            [
                'title'       => 'Payroll & Salary Slips',
                'description' => 'Generate monthly salary slips, configure components, and download PDF payslips.',
                'lessons' => [
                    [
                        'title'   => 'Salary Components',
                        'content' => '<h4>Configuring Salary Components</h4>
<p>Go to <strong>Settings → Salary Components</strong> (Admin only).</p>
<ul>
  <li>Components can be <strong>Earnings</strong> (e.g. Basic, HRA, TA) or <strong>Deductions</strong> (e.g. PF, ESI, TDS).</li>
  <li>Each component can be set as a fixed amount or a percentage of Basic/CTC.</li>
  <li>Mark a component as <strong>Active</strong> for it to appear in salary slips.</li>
</ul>',
                    ],
                    [
                        'title'   => 'Generating a Salary Slip',
                        'content' => '<h4>How to Generate a Payslip</h4>
<ol>
  <li>Go to <strong>Salary Slips</strong> and click <strong>Generate Slip</strong>.</li>
  <li>Select <strong>Employee</strong>, <strong>Month</strong>, and <strong>Year</strong>.</li>
  <li>The system previews the calculated amounts based on attendance, LOP, OT, and configured components. Review all values.</li>
  <li>If correct, click <strong>Save Salary Slip</strong>.</li>
</ol>
<p><strong>Tip:</strong> Use <strong>Calculate (Preview)</strong> first without saving to verify numbers before committing.</p>',
                    ],
                    [
                        'title'   => 'Viewing & Downloading Salary Slips',
                        'content' => '<h4>Salary Slip Actions</h4>
<ol>
  <li>Go to <strong>Salary Slips</strong> and use the filters (Employee, Month, Year) to find the slip.</li>
  <li>Click <strong>View</strong> to open the on-screen salary slip with full breakdown.</li>
  <li>Click <strong>Download PDF</strong> to download a printable PDF payslip with the company logo and address.</li>
  <li>To delete a slip, click the <strong>Delete</strong> icon.</li>
</ol>',
                    ],
                    [
                        'title'   => 'OT & Grace Settings',
                        'content' => '<h4>Overtime Settings</h4>
<p>Go to <strong>Settings → OT Settings</strong> (Admin only) to configure:</p>
<ul>
  <li><strong>Office Start Time</strong> and <strong>Office End Time</strong> – defines standard working hours.</li>
  <li><strong>OT Threshold</strong> – minimum extra minutes before OT is counted.</li>
  <li><strong>OT Rate Multiplier</strong> – typically 2× the hourly rate.</li>
</ul>
<h4>Grace Settings</h4>
<p>Go to <strong>Settings → Grace Settings</strong> to configure:</p>
<ul>
  <li><strong>Grace Time</strong> – minutes after office start before an employee is marked Late.</li>
  <li><strong>Late Deduction Rule</strong> – number of lates that convert to half-day or full-day LOP.</li>
</ul>',
                    ],
                ],
            ],

            // ── 7. Benefits, Bonuses & Loans ─────────────────────────────────
            [
                'title'       => 'Benefits, Bonuses & Loans',
                'description' => 'Manage recurring employee benefits, one-time bonuses, and salary advances/loans.',
                'lessons' => [
                    [
                        'title'   => 'Employee Benefits',
                        'content' => '<h4>Recurring Benefits</h4>
<p>Go to <strong>Employee Benefits</strong> to assign fund-based benefits (e.g. PF, ESI, Gratuity contribution, Insurance) to employees.</p>
<ol>
  <li>Click <strong>Add Benefit</strong>.</li>
  <li>Select <strong>Employee</strong>, <strong>Benefit Fund Type</strong>, and <strong>Frequency</strong> (Monthly, Quarterly, Half-Yearly, Annual, Weekly, Fortnightly).</li>
  <li>Set <strong>Start Date</strong> and optionally an <strong>End Date</strong>.</li>
  <li>Enter the <strong>Amount</strong>.</li>
  <li>Click <strong>Save</strong>.</li>
</ol>
<p>Benefits are automatically included in salary slips for the applicable months based on the selected frequency.</p>',
                    ],
                    [
                        'title'   => 'Employee Bonuses',
                        'content' => '<h4>One-Time Bonuses & Incentives</h4>
<ol>
  <li>Go to <strong>Employee Bonuses</strong> and click <strong>Add Bonus</strong>.</li>
  <li>Select the <strong>Employee</strong>, enter <strong>Bonus Type</strong> (e.g. Performance, Festival), <strong>Amount</strong>, and <strong>Effective Month</strong>.</li>
  <li>Click <strong>Save</strong>. The bonus enters <em>Pending</em> status.</li>
  <li>Admin / Manager can then <strong>Approve</strong> or <strong>Reject</strong> the bonus.</li>
  <li>Once approved, the bonus amount is included in the employee\'s salary slip for that month.</li>
</ol>',
                    ],
                    [
                        'title'   => 'Loans & Advances',
                        'content' => '<h4>Managing Loans</h4>
<ol>
  <li>Go to <strong>Loans</strong> and click <strong>New Loan</strong>.</li>
  <li>Select <strong>Employee</strong>, enter <strong>Loan Amount</strong>, <strong>Purpose</strong>, and <strong>Disbursement Date</strong>.</li>
  <li>Set <strong>Monthly Deduction</strong> (EMI amount to deduct from salary each month).</li>
  <li>Click <strong>Save Loan</strong>.</li>
</ol>
<h5>Recording a Repayment</h5>
<ol>
  <li>Open the loan record and click <strong>Add Repayment</strong>.</li>
  <li>Enter the amount and date, then click <strong>Save</strong>.</li>
  <li>The outstanding balance updates automatically.</li>
</ol>',
                    ],
                ],
            ],

            // ── 8. Letters & Documents ───────────────────────────────────────
            [
                'title'       => 'Letters & Official Documents',
                'description' => 'Generate offer letters, confirmation letters, increment letters, and no-due certificates.',
                'lessons' => [
                    [
                        'title'   => 'Offer Letters',
                        'content' => '<h4>Generating an Offer Letter</h4>
<ol>
  <li>Go to <strong>Offer Letters</strong> and click <strong>Create Offer Letter</strong>.</li>
  <li>Select the <strong>Employee</strong> and fill in CTC, Designation, Joining Date, and any other required fields.</li>
  <li>Click <strong>Save</strong>. The letter is stored.</li>
  <li>Click <strong>View</strong> to preview, or <strong>Download PDF</strong> to generate a signed letter with company letterhead.</li>
</ol>',
                    ],
                    [
                        'title'   => 'Confirmation Letters',
                        'content' => '<h4>Issuing Confirmation Letters</h4>
<p>A Confirmation Letter is issued when an employee successfully completes their probation period.</p>
<ol>
  <li>Go to <strong>Confirmation Letters</strong> and click <strong>Create</strong>.</li>
  <li>Select the <strong>Employee</strong> and enter the <strong>Confirmation Date</strong>.</li>
  <li>Click <strong>Save</strong> and then <strong>Download PDF</strong> for a formal letter with letterhead.</li>
</ol>',
                    ],
                    [
                        'title'   => 'Increment Letters',
                        'content' => '<h4>Salary Increment Letters</h4>
<ol>
  <li>Go to <strong>Increment Letters</strong> and click <strong>Create</strong>.</li>
  <li>Select the <strong>Employee</strong>, enter the <strong>Effective Date</strong>, old CTC, and new CTC.</li>
  <li>Click <strong>Save</strong> and then <strong>Download PDF</strong>.</li>
</ol>
<p><strong>Note:</strong> Creating an increment letter does not automatically update the employee\'s salary. Go to <strong>Increments</strong> under the employee record to apply the salary change in the system.</p>',
                    ],
                    [
                        'title'   => 'No-Due Certificates',
                        'content' => '<h4>No-Due Certificate (NDC)</h4>
<p>Issued when an employee resigns or is relieved, confirming all company assets have been returned.</p>
<ol>
  <li>Go to <strong>No-Due</strong> in the sidebar and click <strong>Create NDC</strong>.</li>
  <li>Select the <strong>Employee</strong> and fill in the relieving date and department clearances.</li>
  <li>Click <strong>Save</strong>. The NDC enters <em>Pending Approval</em> status.</li>
  <li>Admin approves the NDC — the employee can then download the certificate.</li>
</ol>',
                    ],
                ],
            ],

            // ── 9. Company Assets ────────────────────────────────────────────
            [
                'title'       => 'Company Assets',
                'description' => 'Track laptops, phones, and other assets assigned to employees.',
                'lessons' => [
                    [
                        'title'   => 'Adding & Assigning Assets',
                        'content' => '<h4>Asset Management</h4>
<ol>
  <li>Go to <strong>Company Assets</strong> and click <strong>Add Asset</strong>.</li>
  <li>Enter <strong>Asset Name</strong>, <strong>Category</strong> (Laptop, Phone, etc.), <strong>Serial Number</strong>, and <strong>Purchase Date</strong>.</li>
  <li>Click <strong>Save Asset</strong>.</li>
</ol>
<h5>Assigning to an Employee</h5>
<ol>
  <li>On the asset list, click <strong>Assign</strong>.</li>
  <li>Select the <strong>Employee</strong> and <strong>Assignment Date</strong>.</li>
  <li>Click <strong>Confirm Assignment</strong>.</li>
</ol>',
                    ],
                    [
                        'title'   => 'Returning an Asset',
                        'content' => '<h4>Recording an Asset Return</h4>
<ol>
  <li>Find the assigned asset in the list and click <strong>Return</strong>.</li>
  <li>Enter the <strong>Return Date</strong> and <strong>Condition</strong> (Good / Damaged / Lost).</li>
  <li>Click <strong>Process Return</strong>. The asset status reverts to <em>Available</em>.</li>
</ol>',
                    ],
                ],
            ],

            // ── 10. Settings & Administration ────────────────────────────────
            [
                'title'       => 'Settings & Administration',
                'description' => 'Configure entities, departments, designations, users, and roles. (Admin only)',
                'lessons' => [
                    [
                        'title'   => 'Managing Entities',
                        'content' => '<h4>Company Entities</h4>
<p>An Entity represents a legal company or branch. All official letters and salary slips pull company details (name, logo, address) from the assigned entity.</p>
<ol>
  <li>Go to <strong>Settings → Entities</strong> and click <strong>Add Entity</strong>.</li>
  <li>Enter <strong>Name</strong>, <strong>Logo</strong> (upload), <strong>Address</strong>, <strong>Phone</strong>, <strong>Email</strong>, and <strong>Website</strong>.</li>
  <li>Add <strong>Signatory Name</strong> and <strong>Signatory Title</strong> for use in official letters.</li>
  <li>Click <strong>Save</strong>.</li>
</ol>
<p>Assign an entity to each employee on their profile page.</p>',
                    ],
                    [
                        'title'   => 'Departments & Designations',
                        'content' => '<h4>Departments</h4>
<ol>
  <li>Go to <strong>Settings → Departments</strong> and click <strong>Add Department</strong>.</li>
  <li>Enter the department name and click <strong>Save</strong>.</li>
</ol>
<h4>Designations</h4>
<ol>
  <li>Go to <strong>Settings → Designations</strong> and click <strong>Add Designation</strong>.</li>
  <li>Select the <strong>Department</strong> it belongs to, enter the designation name, and click <strong>Save</strong>.</li>
</ol>',
                    ],
                    [
                        'title'   => 'User Accounts & Roles',
                        'content' => '<h4>Creating Users</h4>
<ol>
  <li>Go to <strong>Users</strong> in the sidebar (Admin only) and click <strong>Add User</strong>.</li>
  <li>Enter <strong>Name</strong>, <strong>Email</strong>, and <strong>Password</strong>.</li>
  <li>Assign a <strong>Role</strong>: Admin, Manager, or Staff.</li>
  <li>Click <strong>Save User</strong>.</li>
</ol>
<h5>Role Permissions Summary</h5>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
  <thead><tr style="background:#f0f4ff"><th>Role</th><th>Access Level</th></tr></thead>
  <tbody>
    <tr><td>Admin</td><td>Full access to all modules and settings</td></tr>
    <tr><td>Manager</td><td>Can manage employees, attendance, leaves, and payroll but cannot change settings</td></tr>
    <tr><td>Staff</td><td>Read-only access to their own profile and leave requests</td></tr>
  </tbody>
</table>',
                    ],
                    [
                        'title'   => 'Leave Types & Holiday Types',
                        'content' => '<h4>Leave Types</h4>
<p>Go to <strong>Settings → Leave Types</strong> to add or edit leave categories (e.g. Casual Leave, Sick Leave, Privilege Leave).</p>
<ul>
  <li>Set <strong>Annual Allocation</strong> – number of days per year.</li>
  <li>Toggle <strong>Is Paid</strong> – whether this leave type is paid or unpaid (LOP).</li>
</ul>
<h4>Holiday Types</h4>
<p>Go to <strong>Settings → Holiday Types</strong> to create categories (e.g. National Holiday, Festival, Optional). Each type has a display colour used in the holiday calendar.</p>',
                    ],
                ],
            ],

            // ── 11. Reports ──────────────────────────────────────────────────
            [
                'title'       => 'Reports',
                'description' => 'Access attendance, benefit, bonus, and payroll impact reports.',
                'lessons' => [
                    [
                        'title'   => 'Attendance Report',
                        'content' => '<h4>Monthly Attendance Report</h4>
<p>Go to <strong>Attendance → Report</strong>.</p>
<ol>
  <li>Select <strong>Month</strong>, <strong>Year</strong>, and optionally filter by Department or Employee.</li>
  <li>Click <strong>Generate</strong>.</li>
</ol>
<p>The report shows each employee with:</p>
<ul>
  <li>Days Present, Paid Days, Late, Absent/LOP, OT Hours, and Man Hours (total logged hours).</li>
  <li>Rows with LOP are highlighted in red; late entries in amber.</li>
</ul>',
                    ],
                    [
                        'title'   => 'Benefit Reports',
                        'content' => '<h4>Benefit & Bonus Reports</h4>
<p>Go to <strong>Reports</strong> in the sidebar. Five sub-reports are available:</p>
<ul>
  <li><strong>All Benefits</strong> – list of all active and inactive benefit records.</li>
  <li><strong>Monthly Benefits</strong> – benefits applicable for a selected month, with totals.</li>
  <li><strong>Bonuses</strong> – list of all bonuses filtered by status, employee, or month.</li>
  <li><strong>Employee History</strong> – full benefit and bonus history for a single employee.</li>
  <li><strong>Payroll Impact</strong> – aggregate view of how benefits and bonuses affect the payroll cost for a month.</li>
</ul>',
                    ],
                    [
                        'title'   => 'Activity Log',
                        'content' => '<h4>Activity Log (Admin Only)</h4>
<p>Go to <strong>Activity Log</strong> in the sidebar to see a chronological record of all significant actions performed in the system.</p>
<ul>
  <li>Each entry shows the <strong>User</strong>, <strong>Action</strong>, <strong>Module</strong>, and <strong>Timestamp</strong>.</li>
  <li>Use this for auditing changes to employee records, salary slips, leave approvals, and settings.</li>
</ul>',
                    ],
                ],
            ],

        ];

        foreach ($guide as $moduleIndex => $moduleData) {
            $module = TrainingModule::create([
                'title'        => $moduleData['title'],
                'description'  => $moduleData['description'],
                'role_access'  => [],
                'is_published' => true,
                'created_by'   => $adminId,
            ]);

            foreach ($moduleData['lessons'] as $lessonIndex => $lessonData) {
                TrainingLesson::create([
                    'training_module_id' => $module->id,
                    'title'              => $lessonData['title'],
                    'content'            => $lessonData['content'],
                    'sort_order'         => $lessonIndex + 1,
                ]);
            }
        }

        $this->command->info('HRMS Guide seeded: ' . count($guide) . ' modules created.');
    }
}

