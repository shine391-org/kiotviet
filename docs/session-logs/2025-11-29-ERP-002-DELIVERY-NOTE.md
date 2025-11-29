# Session Log - 2025-11-29 - ERP-002 Delivery Note

## Work
- Thêm migration delivery_notes + delivery_note_items và cập nhật golden schema + truncate list.
- Triển khai clean layers: models, validator, repositories, service (state machine draft→confirmed→shipped/delivered, partial delivery, over-delivery guard, cancel rollback stock), controller + routes, service registry.
- Hook inventory/batch/serial: deduct/restore tồn qua InventoryRepository + MovementLogger + Batch/Serial services.

## Files
- Schema: backend-ci/app/Database/Migrations/2025-11-29-001002_CreateDeliveryNoteTables.php, cập nhật 2025-11-27-000999_TestSchemaSetup.php, DevDatabaseTrait.
- Logic: backend-ci/app/Services/DeliveryNotes/DeliveryNoteService.php; repositories/validators/models/controller/routes/services config.
- Tests: backend-ci/tests/Services/DeliveryNoteServiceTest.php, backend-ci/tests/Integration/Api/DeliveryNotesApiTest.php.
- Task/doc: docs/tasks/ERPNext/ERP-002-DELIVERY-NOTE.md.

## Tests (container)
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/DeliveryNoteServiceTest.php
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Integration/Api/DeliveryNotesApiTest.php

## Notes
- Phát sinh movement logger signature mới (type trước quantity) và đã cập nhật call sites liên quan.
