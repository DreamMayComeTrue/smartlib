/**
 * Optional helper — converts the Technical_Report_Skeleton.md into a real
 * .docx using docx-js. Run from the repo root once the markdown is filled in:
 *
 *   cd smartlib/docs
 *   npm install docx
 *   node generate-report-docx.js
 *
 * The output appears next to the script as `Technical_Report.docx`.
 *
 * If you'd rather use pandoc:
 *   pandoc Technical_Report_Skeleton.md -o Technical_Report.docx \
 *          --reference-doc=reference.docx
 */

import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import {
  Document, Packer, Paragraph, TextRun,
  HeadingLevel, AlignmentType, PageBreak
} from 'docx'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const md = fs.readFileSync(path.join(__dirname, 'Technical_Report_Skeleton.md'), 'utf8')

// Tiny markdown → docx converter. Handles headings, paragraphs, code blocks.
// (Tables and images aren't supported — paste them manually in Word.)
const children = []
let inCode = false
let codeBuffer = []

for (const rawLine of md.split('\n')) {
  const line = rawLine.replace(/\r$/, '')

  // Code fences
  if (line.startsWith('```')) {
    if (inCode) {
      children.push(new Paragraph({
        children: [new TextRun({ text: codeBuffer.join('\n'), font: 'Consolas', size: 18 })],
        spacing: { after: 200 }
      }))
      codeBuffer = []
      inCode = false
    } else {
      inCode = true
    }
    continue
  }
  if (inCode) { codeBuffer.push(line); continue }

  // Headings
  const h = line.match(/^(#{1,4})\s+(.*)$/)
  if (h) {
    const levelMap = {
      1: HeadingLevel.HEADING_1,
      2: HeadingLevel.HEADING_2,
      3: HeadingLevel.HEADING_3,
      4: HeadingLevel.HEADING_4
    }
    children.push(new Paragraph({
      heading: levelMap[h[1].length],
      children: [new TextRun({ text: h[2], bold: true })]
    }))
    continue
  }

  // Horizontal rule → page break
  if (line.trim() === '---') {
    children.push(new Paragraph({ children: [new PageBreak()] }))
    continue
  }

  // Plain paragraph (blank line = empty paragraph for spacing)
  children.push(new Paragraph({
    children: [new TextRun({ text: line })]
  }))
}

const doc = new Document({
  styles: {
    default: { document: { run: { font: 'Calibri', size: 22 } } } // 11pt
  },
  sections: [{
    properties: {
      page: {
        size: { width: 11906, height: 16838 }, // A4
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    children
  }]
})

Packer.toBuffer(doc).then(buf => {
  const out = path.join(__dirname, 'Technical_Report.docx')
  fs.writeFileSync(out, buf)
  console.log('✓ Wrote', out)
})
