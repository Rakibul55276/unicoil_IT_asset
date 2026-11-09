$(function () {
    $('#empId').on('change', function () {
        // This legacy lookup belongs only to forms that provide an employee
        // endpoint. Other screens also use #empId and must keep their own
        // change handlers running.
        if (!$(this).data('url') || typeof getEmpData !== 'function') {
            return;
        }

        const employeeId = $('#empId').val();
        const url = `${$(this).data('url')}/${employeeId}/json`;

        getEmpData(url);
    });

    const $userEmployee = $('#empId[data-current-username]');
    const $userEmail = $('#email');
    const $userFullName = $('#nama');
    if (
        $userEmployee.length &&
        $userEmail.length &&
        $userFullName.length
    ) {
        const originalEmail = $userEmail.val() || '';
        const originalFullName = $userFullName.val() || '';
        const originalEmployee = $userEmployee.val() || '';
        const currentUsername =
            $userEmployee.attr('data-current-username') || '';

        function syncUserEmployeeEmail() {
            const option = $userEmployee.find('option:selected');
            const linked = $userEmployee.val() !== '';
            const employeeEmail = linked
                ? String(option.attr('data-email') || '').trim()
                : '';
            const employeeFullName = linked
                ? String(option.attr('data-fullname') || '').trim()
                : '';
            const linkedUsername = linked
                ? String(option.attr('data-linked-user') || '').trim()
                : '';
            const duplicate =
                linkedUsername !== '' && linkedUsername !== currentUsername;

            $('#employeeHelp')
                .toggleClass('text-danger', duplicate)
                .text(
                    duplicate
                        ? `This employee is already linked to user ID ${linkedUsername}. Each employee can have only one user ID.`
                        : 'Required for self-service requests and receiver acceptance. One employee can be linked to only one login.',
                );
            $('form button[type="submit"]').prop('disabled', duplicate);

            $userFullName.val(
                linked && employeeFullName
                    ? employeeFullName
                    : originalFullName,
            );

            if (employeeEmail) {
                $userEmail
                    .val(employeeEmail)
                    .prop('readonly', true)
                    .prop('required', true);
                $('#missingEmailAlert').addClass('hide');
                $('#emailHelp')
                    .removeClass('text-danger')
                    .addClass('text-success')
                    .text('Email loaded from the linked employee profile.');
                return;
            }

            $userEmail
                .prop('readonly', false)
                .prop('required', true)
                .val(
                    !linked || $userEmployee.val() === originalEmployee
                        ? originalEmail
                        : '',
                );
            $('#emailHelp')
                .removeClass('text-danger')
                .removeClass('text-success')
                .text('');
        }

        window.syncUserEmployeeEmail = syncUserEmployeeEmail;
        $userEmployee.on(
            'change.userEmployeeEmail select2:select.userEmployeeEmail select2:clear.userEmployeeEmail searchable-select-change.userEmployeeEmail',
            syncUserEmployeeEmail,
        );
        syncUserEmployeeEmail();
    }

    $('#btn-register').on('click', function () {
        const departmentSelect = document.getElementById('detpId');
        const selectedDepartment = departmentSelect.options[departmentSelect.selectedIndex].text;

        $('#msg-info').html('');

        if ($('#empId').val() === '') {
            setMessage('danger', 'Input Employee ID');
            return;
        }

        if ($('#detpId').val() === '') {
            setMessage('danger', 'Select Department!');
            return;
        }

        $.ajax({
            url: $(this).data('url'),
            type: 'POST',
            dataType: 'json',
            data: {
                empId: $('#empId').val(),
                deptId: $('#detpId').val(),
                active: '1',
            },
            success: function (result) {
                if (result === 1) {
                    setMessage(
                        'success',
                        `Employee Successfully Assigned to ${selectedDepartment} Department`,
                    );
                    $('#empId').val('');
                    $('#empName').val('');
                    $('#detpId').val('');
                } else if (result === 'X') {
                    setMessage(
                        'danger',
                        `Employee Already Assigned to ${selectedDepartment} Department`,
                    );
                }
            },
        });
    });

    $('#btn-create-asset').on('click', function () {
        $('#msg-info').html('');

        $.ajax({
            url: $(this).data('url'),
            type: 'POST',
            dataType: 'json',
            data: {
                assetKat: $('#assetKat').val(),
                desc: $('#desc').val(),
                empId: $('#empId').val(),
                createdBy: $(this).data('user'),
                createdDate: '2019-02-12',
                quantity: $('#qty').val(),
                unit: $('#unit').val(),
                assetStat: $('#assetStatus').val(),
                location: $('#location').val(),
            },
            success: function (result) {
                if (result === 1) {
                    setMessage('success', 'Asset Created');
                } else if (result === 'X') {
                    setMessage('danger', 'Create Asset Error');
                }
            },
        });
    });

    $('#btn-asset-display').on('click', function () {
        const assetNumber = $('#src-asset-num').val();

        if (assetNumber !== '') {
            getAssetDetails(`${$(this).data('url')}/${assetNumber}`);
        }
    });

    $('#btn-dtl-clsd').on('click', function () {
        $('#src-asset-num').val('');
    });
});

/**
 * Restore non-sensitive POST values after server-side validation redirects.
 * The server supplies this object only once and excludes credentials/tokens.
 */
function restoreRejectedFormInput() {
    const oldInput = window.__oldFormInput;
    if (
        !oldInput ||
        typeof oldInput !== 'object' ||
        Object.keys(oldInput).length === 0
    ) {
        return;
    }

    const fieldRoot = function (name) {
        return String(name || '').split('[', 1)[0];
    };
    const forms = Array.prototype.slice.call(document.querySelectorAll('form'));
    let targetForm = null;
    let bestScore = 0;

    forms.forEach(function (form) {
        const roots = {};
        Array.prototype.forEach.call(form.elements, function (control) {
            if (control.name) roots[fieldRoot(control.name)] = true;
        });
        const score = Object.keys(oldInput).reduce(function (total, key) {
            return total + (roots[key] ? 1 : 0);
        }, 0);
        if (score > bestScore) {
            bestScore = score;
            targetForm = form;
        }
    });

    if (!targetForm) return;

    const dispatchChange = function (control) {
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
    };

    // Equipment request cards use visual checkboxes without a name. Their
    // submitted category[] values identify which cards must be reopened first.
    const selectedCategories = Array.isArray(oldInput.category)
        ? oldInput.category.map(String)
        : [];
    if (selectedCategories.length) {
        Array.prototype.forEach.call(
            targetForm.querySelectorAll('.equipment-check'),
            function (checkbox) {
                checkbox.checked = selectedCategories.indexOf(String(checkbox.value)) !== -1;
                dispatchChange(checkbox);
            },
        );
    }

    const arrayOffsets = {};
    Array.prototype.forEach.call(targetForm.elements, function (control) {
        if (!control.name || control.disabled) return;
        const type = String(control.type || '').toLowerCase();
        if (
            ['password', 'file', 'submit', 'button', 'reset', 'image'].indexOf(type) !== -1 ||
            control.name === 'csrf_token' ||
            control.hasAttribute('data-no-old-input')
        ) {
            return;
        }

        const root = fieldRoot(control.name);
        if (!Object.prototype.hasOwnProperty.call(oldInput, root)) return;
        let value = oldInput[root];
        const pathParts = [];
        String(control.name).replace(/\[([^\]]*)\]/g, function (_match, part) {
            pathParts.push(part);
            return _match;
        });

        pathParts.forEach(function (part) {
            if (part !== '' && value && typeof value === 'object') {
                value = value[part];
            }
        });

        if (type === 'checkbox' || type === 'radio') {
            const values = Array.isArray(value) ? value.map(String) : [String(value)];
            control.checked = values.indexOf(String(control.value)) !== -1;
            dispatchChange(control);
            return;
        }

        if (control.multiple && control.options) {
            const values = Array.isArray(value) ? value.map(String) : [String(value)];
            Array.prototype.forEach.call(control.options, function (option) {
                option.selected = values.indexOf(String(option.value)) !== -1;
            });
            dispatchChange(control);
            return;
        }

        if (pathParts.indexOf('') !== -1 && Array.isArray(value)) {
            const offset = arrayOffsets[control.name] || 0;
            arrayOffsets[control.name] = offset + 1;
            value = value[offset];
        }
        if (value === undefined || value === null || typeof value === 'object') return;

        control.value = String(value);
        dispatchChange(control);
    });

    window.__oldFormInput = null;
}

document.addEventListener('DOMContentLoaded', restoreRejectedFormInput);

/**
 * Add consistent header-click sorting to the application's regular tables.
 *
 * DataTables manages #dynamic-table separately. For the remaining tables this
 * sorter keeps rowspan-based issue/return batches and summary rows together,
 * so sorting never separates assets that belong to the same transaction.
 */
function initializeApplicationTableSorting() {
    const tables = document.querySelectorAll('table');

    const normalizeText = function (value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    };

    const compareValues = function (left, right) {
        const leftText = normalizeText(left);
        const rightText = normalizeText(right);
        const numericPattern = /^[-+]?\d+(?:,\d{3})*(?:\.\d+)?$/;

        if (numericPattern.test(leftText) && numericPattern.test(rightText)) {
            return Number(leftText.replace(/,/g, '')) - Number(rightText.replace(/,/g, ''));
        }

        const leftDate = leftText.match(/\b(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        const rightDate = rightText.match(/\b(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (leftDate && rightDate) {
            const toTimestamp = function (parts) {
                return Date.UTC(
                    Number(parts[1]),
                    Number(parts[2]) - 1,
                    Number(parts[3]),
                    Number(parts[4] || 0),
                    Number(parts[5] || 0),
                    Number(parts[6] || 0),
                );
            };
            return toTimestamp(leftDate) - toTimestamp(rightDate);
        }

        return leftText.localeCompare(rightText, undefined, {
            numeric: true,
            sensitivity: 'base',
        });
    };

    const cellForColumn = function (row, columnIndex) {
        let position = 0;
        const cells = Array.prototype.slice.call(row.cells || []);

        for (let index = 0; index < cells.length; index += 1) {
            const cell = cells[index];
            const width = Math.max(1, Number(cell.colSpan) || 1);
            if (columnIndex >= position && columnIndex < position + width) {
                return cell;
            }
            position += width;
        }

        return null;
    };

    const buildRowBlocks = function (tbody) {
        const rows = Array.prototype.slice.call(tbody.children).filter(function (row) {
            return row.tagName === 'TR';
        });
        const blocks = [];

        for (let index = 0; index < rows.length;) {
            const firstRow = rows[index];
            const blockRows = [firstRow];

            if (firstRow.classList.contains('issue-summary-row')) {
                index += 1;
                while (
                    index < rows.length &&
                    !rows[index].classList.contains('issue-summary-row')
                ) {
                    blockRows.push(rows[index]);
                    index += 1;
                }
            } else {
                const rowSpan = Array.prototype.reduce.call(
                    firstRow.cells || [],
                    function (largest, cell) {
                        return Math.max(largest, Number(cell.rowSpan) || 1);
                    },
                    1,
                );
                for (let offset = 1; offset < rowSpan && index + offset < rows.length; offset += 1) {
                    blockRows.push(rows[index + offset]);
                }
                index += blockRows.length;
            }

            blocks.push({ rows: blockRows, originalIndex: blocks.length });
        }

        return blocks;
    };

    const blockSortValue = function (block, columnIndex) {
        for (let index = 0; index < block.rows.length; index += 1) {
            const row = block.rows[index];
            if (row.classList.contains('issue-summary-row')) continue;

            const cell = cellForColumn(row, columnIndex);
            if (!cell) continue;
            if (cell.hasAttribute('data-sort-value')) {
                return cell.getAttribute('data-sort-value');
            }

            const value = normalizeText(cell.textContent);
            if (value !== '') return value;
        }

        return '';
    };

    tables.forEach(function (table) {
        if (
            table.id === 'dynamic-table' ||
            table.classList.contains('dataTable') ||
            table.classList.contains('js-no-sort') ||
            table.getAttribute('data-sortable') === 'false' ||
            table.closest('.modal')
        ) {
            return;
        }

        const headerRows = table.tHead ? table.tHead.rows : [];
        const tbody = table.tBodies && table.tBodies.length ? table.tBodies[0] : null;
        if (!headerRows.length || !tbody || table.dataset.applicationSortReady === 'true') {
            return;
        }

        const headerRow = headerRows[headerRows.length - 1];
        const headers = Array.prototype.slice.call(headerRow.cells || []);
        if (!headers.length) return;

        table.dataset.applicationSortReady = 'true';
        table.classList.add('application-sortable-table');

        headers.forEach(function (header, columnIndex) {
            const label = normalizeText(header.textContent).toLowerCase();
            const excludedLabel = /^(no\.?|#|action|asset action|actions|select|documents?|image|qr(?: code)?)$/;
            const isSortable =
                header.getAttribute('data-sort') !== 'false' &&
                Number(header.colSpan || 1) === 1 &&
                !header.querySelector('input[type="checkbox"]') &&
                !excludedLabel.test(label);

            if (!isSortable) {
                header.classList.add('application-sort-disabled');
                return;
            }

            header.classList.add('application-sort-header');
            header.setAttribute('role', 'button');
            header.setAttribute('tabindex', '0');
            header.setAttribute('aria-sort', 'none');
            header.setAttribute('title', `Sort by ${normalizeText(header.textContent)}`);

            const sortColumn = function () {
                const nextDirection = header.getAttribute('aria-sort') === 'ascending'
                    ? 'descending'
                    : 'ascending';
                const direction = nextDirection === 'ascending' ? 1 : -1;
                const blocks = buildRowBlocks(tbody);

                blocks.sort(function (left, right) {
                    const result = compareValues(
                        blockSortValue(left, columnIndex),
                        blockSortValue(right, columnIndex),
                    );
                    return result === 0
                        ? left.originalIndex - right.originalIndex
                        : result * direction;
                });

                const fragment = document.createDocumentFragment();
                blocks.forEach(function (block) {
                    block.rows.forEach(function (row) {
                        fragment.appendChild(row);
                    });
                });
                tbody.appendChild(fragment);

                headers.forEach(function (otherHeader) {
                    if (otherHeader !== header && otherHeader.hasAttribute('aria-sort')) {
                        otherHeader.setAttribute('aria-sort', 'none');
                    }
                });
                header.setAttribute('aria-sort', nextDirection);
            };

            header.addEventListener('click', sortColumn);
            header.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    sortColumn();
                }
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeApplicationTableSorting);

function getAssetDetails(url) {
    $.ajax({
        url: url,
        type: 'GET',
        success: function (result) {
            const imageSource = result.assetImage
                ? `${window.BASEURL}/Asset/media/${encodeURIComponent(result.assetImage)}`
                : `${window.BASEURL}/public/img/no-image.png`;

            // Preload the image so a missing upload produces a clear message.
            const image = new Image();
            image.onerror = function () {
                alert(`Failed to load image: ${imageSource}`);
            };
            image.src = imageSource;

            $('#assetDetailModalLabel').html(`Asset ${result.assetNum} Details`);
            $('#asset-detail').html(`
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <br><br><br>
                            <img src="${imageSource}" class="img-fluid" style="max-height:250px;">
                        </div>
                        <div class="col-md-8">
                            <ul class="list-group">
                                <li class="list-group-item">Asset Number : ${result.assetNum}</li>
                                <li class="list-group-item">Asset Description : ${result.Description}</li>
                                <li class="list-group-item">Asset Category : ${result.assetKatName}</li>
                                <li class="list-group-item">Employee ID : ${result.empId}</li>
                                <li class="list-group-item">Employee Name : ${result.empName}</li>
                                <li class="list-group-item">Quantity : ${result.quantity}${result.unit}</li>
                                <li class="list-group-item">Location : ${result.locationName}</li>
                                <li class="list-group-item">Status : ${result.assetStatusName}</li>
                                <li class="list-group-item">Created By : ${result.createdBy}</li>
                                <li class="list-group-item">Created Date : ${result.createdDate}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `);
        },
        error: function () {
            alert('Error fetching asset details. Please try again.');
        },
    });
}
