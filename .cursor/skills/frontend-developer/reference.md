# Frontend Developer — reference

Examples and templates. Match **project** framework, styling, and libraries.

## React: virtualized table (illustrative)

Uses `@tanstack/react-virtual`. Install in the project if missing; adjust styling to the design system.

```tsx
import React, { memo, useCallback, useRef } from 'react';
import { useVirtualizer } from '@tanstack/react-virtual';

export interface Column {
  key: string;
  header: string;
}

interface DataTableProps {
  data: Array<Record<string, unknown>>;
  columns: Column[];
  onRowClick?: (row: Record<string, unknown>) => void;
}

export const DataTable = memo(function DataTable({
  data,
  columns,
  onRowClick,
}: DataTableProps) {
  const parentRef = useRef<HTMLDivElement>(null);

  const rowVirtualizer = useVirtualizer({
    count: data.length,
    getScrollElement: () => parentRef.current,
    estimateSize: () => 50,
    overscan: 5,
  });

  const handleRowClick = useCallback(
    (row: Record<string, unknown>) => {
      onRowClick?.(row);
    },
    [onRowClick],
  );

  return (
    <div
      ref={parentRef}
      className="h-96 overflow-auto"
      role="table"
      aria-label="Data table"
    >
      {rowVirtualizer.getVirtualItems().map((virtualItem) => {
        const row = data[virtualItem.index];
        return (
          <div
            key={virtualItem.key}
            className="flex cursor-pointer items-center border-b hover:bg-gray-50"
            role="row"
            tabIndex={0}
            onClick={() => handleRowClick(row)}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleRowClick(row);
              }
            }}
          >
            {columns.map((column) => (
              <div key={column.key} className="flex-1 px-4 py-2" role="cell">
                {String(row[column.key] ?? '')}
              </div>
            ))}
          </div>
        );
      })}
    </div>
  );
});
```

Note: For production tables, add proper `<table>` semantics or a documented grid pattern, column headers (`role="columnheader"`), and row selection semantics as required.

## Frontend implementation handoff (Markdown)

```markdown
# [Project Name] Frontend Implementation

## UI implementation
- **Framework**: [e.g. React 19 + reasoning]
- **State / data**: [e.g. TanStack Query, Redux, Zustand, server components]
- **Styling**: [e.g. Tailwind, CSS Modules, styled-system]
- **Components**: [shared library paths, naming]

## Performance
- **Core Web Vitals**: [targets — e.g. LCP, INP, CLS; how measured]
- **Bundle**: [code splitting, lazy routes, heavy deps]
- **Images / assets**: [formats, dimensions, lazy loading]
- **Caching**: [HTTP, service worker, or N/A]

## Accessibility
- **WCAG**: [2.1 AA checklist highlights]
- **Keyboard / focus**: [dialogs, menus, composite widgets]
- **Motion**: [`prefers-reduced-motion` handling]
- **Testing**: [axe, manual screen reader spot checks, etc.]

---

**Date**: [Date]
**Status**: Ready for review / merge
```

## Benchmarks (tune to product)

Original rule suggested aggressive targets; treat as **aspirational** unless product SLOs say otherwise:

- Fast experience on constrained networks (define “fast” with your metrics)
- Strong Lighthouse **Performance** and **Accessibility** where applicable
- No avoidable production console errors
- High reuse of shared components where it reduces drift

## Editor / bridge work (when in scope)

- Navigation commands: open, reveal, peek — keep perceived latency low
- Clear UI for connection / sync state
- Defensive handling of dropped WebSocket or RPC channels
