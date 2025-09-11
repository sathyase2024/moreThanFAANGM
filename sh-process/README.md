SH Process Flow (WordPress Plugin)

Responsive, animated Our Process component:
- Desktop: horizontal timeline with clickable steps
- Mobile: accessible accordion with smooth expand/collapse

Install
1. Zip the sh-process folder or copy it to wp-content/plugins/sh-process.
2. Activate "SH Process Flow" in WordPress Admin → Plugins.

Shortcodes

Container
```
[sh_process id="process-1" active="1" class=""]
  ...steps here...
[/sh_process]
```
- active: default selected step on desktop (1-based)

Step
```
[sh_step index="1" title="Fill in the enquiry form" subtitle="Kick-off" icon=""]
Your step description goes here. Supports shortcodes and basic HTML.
[/sh_step]
```
- index: numeric label shown on the marker
- icon: optional image URL (uses a dot if omitted)

Example (7 steps)
```
[sh_process active="1"]
  [sh_step index="1" title="Fill in the enquiry form" subtitle="Start"]We will contact you for details.[/sh_step]
  [sh_step index="2" title="Architect call & site visit" subtitle="Understand"]Site study and requirement capture.[/sh_step]
  [sh_step index="3" title="Design brief & proposal" subtitle="Plan"]Scope, timelines, commercials.[/sh_step]
  [sh_step index="4" title="Design & drawings" subtitle="Create"]Concepts, 3D views, structural inputs.[/sh_step]
  [sh_step index="5" title="BOQ & agreements" subtitle="Finalize"]Milestones, specs, payment terms.[/sh_step]
  [sh_step index="6" title="Approvals & kickoff" subtitle="Begin"]Mobilization and procurement.[/sh_step]
  [sh_step index="7" title="Track & handover" subtitle="Deliver"]Execution updates and completion.[/sh_step]
[/sh_process]
```

Notes
- Keyboard: Arrow keys navigate steps on desktop; Enter/Space toggles.
- Accessibility: Tabs use role=tab/tabpanel, panels are hidden with hidden attribute.

