ALTER TABLE department ADD COLUMN deptPrefix varchar(10) NULL AFTER deptId;

UPDATE department SET deptPrefix = CASE deptName
    WHEN 'AUTOMATION' THEN 'AUT'
    WHEN 'COMMERCIAL' THEN 'COM'
    WHEN 'FINANCE' THEN 'FIN'
    WHEN 'HR ADMIN' THEN 'HA'
    WHEN 'INFORMATION TECHNOLOGY' THEN 'IT'
    WHEN 'LOGISTICS' THEN 'LOG'
    WHEN 'OPERATIONS ADMIN' THEN 'OA'
    WHEN 'QA/LAB' THEN 'QA'
    WHEN 'SALES' THEN 'SAL'
    WHEN 'WAREHOUSE' THEN 'WAR'
    ELSE CONCAT('D', deptId)
END;

ALTER TABLE department
    MODIFY deptPrefix varchar(10) NOT NULL,
    ADD UNIQUE KEY uq_department_prefix (deptPrefix);
