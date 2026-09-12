---
paths:
  - 'app/Mcp/**'
---

# Mcp

## CRM MCP tools map to portfolio records
CRM MCP tools search Person, BusinessEntity, and Asset (not ContactList). GetContactTool activity includes notes, reminders, mail, documents, transactions, and invoices. There is no Deal model — do not add deal-status updates until product defines that mapping. Mutation tools (LogNoteTool, LogFollowUpTool) must honour canMutatePortfolio / closed entities.
