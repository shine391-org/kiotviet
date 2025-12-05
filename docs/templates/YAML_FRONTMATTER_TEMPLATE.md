---
# YAML Frontmatter Template cho LanoCRM Documentation
# Copy và paste vào đầu mỗi file markdown

# Basic Information
title: "Tiêu đề Document"
id: "UNIQUE-ID-FORMAT"  # Format: MODULE-TYPE-NNN (ví dụ: TASK-001-ORDER-CREATE)
priority: "P0|P1|P2|P3"  # P0=Blocker, P1=High, P2=Medium, P3=Low
status: "Done|In Progress|Backlog|Blocked"
module: "Module Name"  # Order, Cash, Inventory, etc.
type: "Implementation|Refactor|Task|Documentation|Audit"
tags: ["tag1", "tag2", "tag3"]  # Maximum 5 tags

# Relationships
dependencies: "ID-1, ID-2"  # Comma-separated task IDs
related_to: "ID-1, ID-2"  # Related documents
implements: "REQUIREMENT-ID"  # If implements specific requirement
part_of: "EPIC-ID"  # If part of larger epic

# Metadata
purpose: "Brief description of document purpose"
location: "path/to/file"
author: "Author Name"
created_date: "YYYY-MM-DD"
last_updated: "YYYY-MM-DD"
version: "1.0"

# Review & Approval
reviewed_by: "Reviewer Name"
approved_by: "Approver Name"
review_date: "YYYY-MM-DD"

# Additional Fields (optional)
estimated_effort: "X days|hours"
actual_effort: "X days|hours"
complexity: "Low|Medium|High"
risk_level: "Low|Medium|High"

# Testing Information (for implementation tasks)
test_coverage: "XX%"
test_files: ["path/to/test1", "path/to/test2"]
integration_tests: "Yes|No|Partial"

# Deployment Information
deployment_status: "Not Started|In Progress|Done|Blocked"
deployment_date: "YYYY-MM-DD"
rollback_plan: "Yes|No"

# Documentation Network
links_to: ["doc-id-1", "doc-id-2"]  # Documents this links to
linked_from: ["doc-id-1", "doc-id-2"]  # Documents that link to this
---

# Document Content Starts Here