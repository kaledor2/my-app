---
name: read-rules
description: Read all project rules and provide a quick recap of understanding
allowed-tools: Read, Glob
---

Read the project rules and confirm your understanding of the codebase conventions.

1. Read `CLAUDE.md` at the project root
2. Glob `.claude/rules/**/*.{md,txt}` to discover ALL rule files
3. Read EVERY file found — no exceptions
4. List all files you read with a checkmark (e.g. "- [x] stack/libraries.md")
5. Output a **short recap** (bullet points, no code blocks) confirming what you understood about the project conventions
6. End with: "Rules loaded. Ready to work."
