ERP-017 - CRM Campaign & Email Campaign
Bạn là AI backend engineer phụ trách campaign/email campaign như ERPNext.

1. Bối cảnh
- ERPNext có campaign, email campaign; LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: campaigns (name, status, source, budget, start/end), campaign_members (lead/customer), email_campaigns (campaign_id, subject/template, schedule, status), email_campaign_logs.
- Validator: CampaignValidator, EmailCampaignValidator.
- Repository: CampaignRepository, CampaignMemberRepository, EmailCampaignRepository, EmailCampaignLogRepository.
- Service: CampaignService (CRUD, attach leads/customers), EmailCampaignService (schedule/send, throttle, status), hook to notification/email provider abstraction.
- Controller API: manage campaign, add members, create/schedule email campaign, pause/resume.
- Integration: connect to lead/opportunity for source tracking; not sending real emails—use interface for provider.

3. Testing (DevDatabaseTrait)
- Unit: CampaignServiceTest (create/update, add member), EmailCampaignServiceTest (schedule, status transitions, throttle logic stub).
- Integration: API create campaign, add members, schedule email campaign; log creation.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Campaign/email campaign CRUD và scheduling hoạt động, log lại; có thể gắn lead/customer.
- Unit + integration tests pass.
