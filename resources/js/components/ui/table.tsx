"use client"

import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * On phones every table turns into a stack of rows: the column headings are hidden and each cell
 * shows its own heading beside its value. The headings are copied onto the cells (data-label) in
 * the browser, so the ~100 existing tables get this without being edited one by one.
 * Pass `stack={false}` for a table that must stay a real grid (for example a marks sheet).
 */
export function labelCells(table: HTMLTableElement) {
  const headRow = table.tHead?.rows[table.tHead.rows.length - 1]
  if (!headRow) return
  const labels: string[] = []
  Array.from(headRow.cells).forEach((th) => {
    const text = (th.textContent ?? "").replace(/\s+/g, " ").trim()
    for (let i = 0; i < Math.max(1, th.colSpan); i++) labels.push(text)
  })
  Array.from(table.tBodies).forEach((body) => {
    Array.from(body.rows).forEach((row) => {
      let col = 0
      let leadFound = false
      Array.from(row.cells).forEach((cell) => {
        const wide = cell.colSpan > 1
        const label = wide ? "" : labels[col] ?? ""
        // a "#" column only numbers the rows, so it is hidden on phones
        if (label === "#") cell.dataset.index = ""
        else delete cell.dataset.index
        // the first real column leads the row (name, receipt number...)
        const primary = !wide && !leadFound && label !== "#" && label.length > 1
        if (primary) cell.dataset.primary = ""
        else delete cell.dataset.primary
        if (label !== "#" && !wide) leadFound = true
        if (label) {
          if (cell.dataset.label !== label) cell.dataset.label = label
        } else if (cell.dataset.label !== undefined) {
          delete cell.dataset.label
        }
        col += Math.max(1, cell.colSpan)
      })
    })
  })
}

function Table({
  className,
  stack = true,
  ...props
}: React.ComponentProps<"table"> & { stack?: boolean }) {
  const ref = React.useRef<HTMLTableElement>(null)

  React.useEffect(() => {
    const table = ref.current
    if (!table || !stack) return
    labelCells(table)
    let frame = 0
    const observer = new MutationObserver(() => {
      cancelAnimationFrame(frame)
      frame = requestAnimationFrame(() => labelCells(table))
    })
    observer.observe(table, { childList: true, subtree: true, characterData: true })
    return () => {
      cancelAnimationFrame(frame)
      observer.disconnect()
    }
  }, [stack])

  return (
    <div
      data-slot="table-container"
      data-stack={stack ? "" : undefined}
      className="relative w-full overflow-x-auto"
    >
      <table
        ref={ref}
        data-slot="table"
        className={cn("w-full caption-bottom text-sm", className)}
        {...props}
      />
    </div>
  )
}

function TableHeader({ className, ...props }: React.ComponentProps<"thead">) {
  return (
    <thead
      data-slot="table-header"
      className={cn("[&_tr]:border-b", className)}
      {...props}
    />
  )
}

function TableBody({ className, ...props }: React.ComponentProps<"tbody">) {
  return (
    <tbody
      data-slot="table-body"
      className={cn("[&_tr:last-child]:border-0", className)}
      {...props}
    />
  )
}

function TableFooter({ className, ...props }: React.ComponentProps<"tfoot">) {
  return (
    <tfoot
      data-slot="table-footer"
      className={cn(
        "border-t bg-muted/50 font-medium [&>tr]:last:border-b-0",
        className
      )}
      {...props}
    />
  )
}

function TableRow({ className, ...props }: React.ComponentProps<"tr">) {
  return (
    <tr
      data-slot="table-row"
      className={cn(
        "border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted",
        className
      )}
      {...props}
    />
  )
}

function TableHead({ className, ...props }: React.ComponentProps<"th">) {
  return (
    <th
      data-slot="table-head"
      className={cn(
        "h-10 px-2 text-left align-middle font-medium whitespace-nowrap text-foreground [&:has([role=checkbox])]:pr-0",
        className
      )}
      {...props}
    />
  )
}

function TableCell({ className, ...props }: React.ComponentProps<"td">) {
  return (
    <td
      data-slot="table-cell"
      className={cn(
        "p-2 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0",
        className
      )}
      {...props}
    />
  )
}

function TableCaption({
  className,
  ...props
}: React.ComponentProps<"caption">) {
  return (
    <caption
      data-slot="table-caption"
      className={cn("mt-4 text-sm text-muted-foreground", className)}
      {...props}
    />
  )
}

export {
  Table,
  TableHeader,
  TableBody,
  TableFooter,
  TableHead,
  TableRow,
  TableCell,
  TableCaption,
}
