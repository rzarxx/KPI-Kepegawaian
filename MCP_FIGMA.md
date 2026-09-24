# MCP FIGMA — Codex Integration

## 1. Remote Server

Use official remote endpoint:

```text
https://mcp.figma.com/mcp
```

## 2. Codex Setup

```bash
codex mcp add figma --url https://mcp.figma.com/mcp
```

OAuth:
complete login in browser.

Verify:
```bash
codex mcp list
codex mcp get figma
```

## 3. Primary Design

```text
https://www.figma.com/design/EmIOtdlEaEnJR1f4PryW4m
```

## 4. Important Rule

MCP connected does NOT mean Codex automatically knows which Figma file to use.

This document + `AGENTS.md` define the project design source.

## 5. Startup Prompt Recommended

```text
Baca AGENTS.md, PRD.md, DESIGN.md, ARCHITECTURE.md dan dokumen project terkait.

Gunakan Figma MCP dan inspect Figma source yang didefinisikan di AGENTS.md.

Jangan menulis kode terlebih dahulu.

Pastikan:
- file Figma dapat diakses
- halaman/frame utama terbaca
- variables/design tokens terbaca
- typography terbaca
- responsive structure dipahami

Laporkan pemahaman sebelum implementasi.
```

## 6. Per-Page Workflow

Best:
Figma → select frame → Copy link to selection.

Prompt:
```text
Implementasikan frame berikut menggunakan Figma MCP:
<FIGMA NODE URL>

Figma adalah source of truth.
Pertahankan layout, spacing, typography, colors, icon, state, dan responsive intent.
```

## 7. Safety

Codex should:
- read Figma before code
- not write to Figma unless explicitly asked
- not invent alternate dashboard style
- use existing project components
- preserve Bahasa Indonesia

## 8. Common Error Prevention

- use remote MCP only
- do not mix URL + stdio config for same server
- restart Codex after auth changes
- verify correct Figma account
- avoid excessive MCP servers
- remove broken unused MCP servers if startup becomes noisy

## 9. Laravel Boost Note

Laravel Boost is a separate MCP.
Its failure should not be confused with Figma.

If Boost startup is unsupported or broken:
disable/fix it separately before diagnosing Figma.
