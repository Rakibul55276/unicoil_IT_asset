INSERT INTO employee(empId,firstname,lastname,fullname,email,deptId,aktif) VALUES
(9001,'QA','Employee','QA Employee','qa.emp@test.local','D010',1),(9002,'QA','DeptMgr','QA DeptMgr','qa.dm@test.local','D010',1),
(9003,'QA','ITMgr','QA ITMgr','qa.itm@test.local','D010',1),(9004,'QA','Issuer','QA Issuer','qa.iss@test.local','D010',1),
(9005,'QA','HR','QA HR','qa.hr@test.local','D010',1),(9006,'QA','Admin','QA Admin','qa.adm@test.local','D010',1),
(9007,'QA','OtherEmp','QA OtherEmp','qa.emp2@test.local','D001',1),(9008,'QA','OtherDM','QA OtherDM','qa.dm2@test.local','D001',1);
INSERT INTO empdept(empId,deptId,active) VALUES (9001,'D010',1),(9002,'D010',1),(9003,'D010',1),(9004,'D010',1),(9005,'D010',1),(9006,'D010',1),(9007,'D001',1),(9008,'D001',1);
UPDATE department SET headEmpId=9002 WHERE deptId='D010';
UPDATE department SET headEmpId=9008 WHERE deptId='D001';
INSERT INTO users(username,email,nama,empId,password,active,registeredOn) VALUES
('qa_emp','qa.emp@test.local','QA Employee',9001,'__HASH__',1,CURDATE()),('qa_dm','qa.dm@test.local','QA DeptMgr',9002,'__HASH__',1,CURDATE()),
('qa_itm','qa.itm@test.local','QA ITMgr',9003,'__HASH__',1,CURDATE()),('qa_iss','qa.iss@test.local','QA Issuer',9004,'__HASH__',1,CURDATE()),
('qa_hr','qa.hr@test.local','QA HR',9005,'__HASH__',1,CURDATE()),('qa_adm','qa.adm@test.local','QA Admin',9006,'__HASH__',1,CURDATE()),
('qa_emp2','qa.emp2@test.local','QA OtherEmp',9007,'__HASH__',1,CURDATE()),('qa_dm2','qa.dm2@test.local','QA OtherDM',9008,'__HASH__',1,CURDATE()),
('qa_inactive','qa.inact@test.local','QA Inactive',NULL,'__HASH__',0,CURDATE());
INSERT INTO user_role(username,role_code,assigned_by) VALUES
('qa_dm','DEPARTMENT_MANAGER','qa'),('qa_itm','IT_MANAGER','qa'),('qa_iss','ISSUER','qa'),('qa_hr','HR_REQUESTER','qa'),
('qa_adm','ADMINISTRATOR','qa'),('qa_dm2','DEPARTMENT_MANAGER','qa'),('qa_inactive','ADMINISTRATOR','qa');
UPDATE users SET active=0 WHERE username='1622';
