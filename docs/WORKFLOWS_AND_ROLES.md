# Workflows and Role Rules

## Manual lifecycle

`RECEIPT -> IN STOCK -> ASSIGN (ISS) -> ASSIGNED -> RETURN (RET) -> IN STOCK`

- Receipt creates the asset and its initial condition.
- Assignment creates one ISS batch for one custodian and up to eight assets.
- Return selects assets from one ISS batch, creates one RET batch, records a condition per asset, and restores stock.
- `assetdata` is the source of the current condition/status; transaction tables retain historical snapshots.

## Request-based lifecycle (feature switch enabled)

Typical sequence:

`CREATED -> DEPARTMENT APPROVAL (when applicable) -> IT APPROVAL (when applicable) -> WAITING FOR ISSUER -> ISSUED -> RECEIVER ACCEPTANCE -> COMPLETED`

Rules:

- A requester cannot approve their own request.
- A department request is available only to the appropriate department manager step.
- A queued approver receives access after the previous step completes.
- Issuer selection is an issue operation, not an approval step.
- One approved request is issued once; partial repeat submissions are not the normal delivery flow.
- The receiver accepts the actual issued assets.
- Administrators can perform permitted corrective forward/backward status actions, but corrections remain in the audit trail.

## Approval routing by requester type

The application builds the route from the requester, target employee/department, assigned roles, and self-approval safeguards. Current simplified intent:

- Ordinary employee/HR-on-behalf requests use the applicable department approval, then IT approval, then Issuer.
- A Department Manager request cannot be self-approved and advances to IT approval.
- An IT Manager request cannot be self-approved and advances to Issuer according to the configured simplified route.
- Missing optional approvers are skipped by the route builder rather than blocking the request.

Always validate routing with test accounts after changing employee departments, department heads, or user roles.

## Return-request lifecycle

`ACTIVE CUSTODY -> RETURN REQUEST (RRQ) -> ISSUER PHYSICAL RETURN -> RET COMPLETED -> IN STOCK`

- One RRQ may contain one to eight assets from the same ISS batch.
- RRQ creation does not modify stock.
- Issuer completes the return with per-asset conditions.
- The completed RET/PDF contains the whole selected batch.

## Scrap lifecycle

`ELIGIBLE ASSET -> SCRAP REQUEST -> IT MANAGER APPROVE/REJECT -> SCRAP LIST/UNCHANGED`

The general request workflow is shown only when `workflow_ui_enabled` is true. Scrap approval is controlled independently by `scrap_approval_enabled`. Deleting a scrapped asset is a separate Administrator action and must retain an archive/audit snapshot.

Issuer asset-operation navigation can be enabled separately with `issuer_ui_enabled`. This does not expose User Access or destructive controls; those remain Administrator-only in the interface.

## Role/action matrix

| Action | Employee | HR Requester | Dept. Manager | IT Manager | Issuer | Administrator |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Request for self | Yes | Yes | Yes | Yes | If linked | Yes |
| Request for another employee | No | Yes | No | No | No | Yes |
| Request for department | No | No | Yes | No | No | Yes |
| Department approval | No | No | Eligible, not own | No | No | Yes/corrective |
| IT approval | No | No | No | Eligible, not own | No | Yes/corrective |
| Receive/assign/return assets | No | No | No | No | Yes | Yes |
| Receiver acceptance | Own custody | Own custody | Own custody | Own custody | Own custody | Corrective access |
| Request scrap | No | No | No | No | Yes | Yes |
| Approve scrap | No | No | No | Yes | No | Yes |
| Master/user administration | No | Employee scope | Limited | Limited | Operational | Full |

The controller authorization is the final authority if a screen and this summary ever differ.
