# Administration and Security Module

## Responsibility

Authentication, user/role access, employee/department organization, master data, profiles, feature switches, and administrative safeguards.

## Files by area

- Login/session: `Home.php`, `Home_model.php`, `Auth.php`, `views/home`
- User access/profile: `User.php`, `User_model.php`, `views/user`
- Employees: `Employee.php`, `Employee_model.php`, `views/employee`
- Departments/heads: `Department.php`, `Department_model.php`, `views/department`
- Categories: `Assetkat.php`, `Assetkat_model.php`, `views/assetkat`
- Locations: `Location.php`, `Location_model.php`, `views/location`
- Units: `Unit.php`, `Unit_model.php`, `views/unit`
- Vendors: `Vendor.php`, `Vendor_model.php`, `views/vendor`
- Configuration: root private `config.php`, template `config.example.php`, `app/config/config.php`

## Critical invariants

- One employee maps to at most one active login.
- Employee ID editing is Administrator-only and must update dependent references safely.
- Controller guards protect all write actions.
- Used master records are not deleted.
- Passwords are hashed, never stored or logged in plaintext.
- Feature switches do not delete data or bypass backend authorization.
