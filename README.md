[![Latest Version on Packagist](http://img.shields.io/packagist/v/diversworld/contao-diveclub-bundle.svg?style=flat)](https://packagist.org/packages/diversworld/contao-diveclub-bundle)
![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fdiversworld%2Fcontao-diveclub-bundle%2Fmain%2Fcomposer.json&query=%24.require%5B%22contao%2Fcore-bundle%22%5D&label=Contao%20Version)
[![Installations via composer per month](http://img.shields.io/packagist/dm/diversworld/contao-diveclub-bundle.svg?style=flat)](https://packagist.org/packages/diversworld/contao-diveclub-bundle)
[![Installations via composer total](http://img.shields.io/packagist/dt/diversworld/contao-diveclub-bundle.svg?style=flat)](https://packagist.org/packages/diversworld/contao-diveclub-bundle)
![Packagist License](https://img.shields.io/packagist/l/diversworld/contao-diveclub-bundle)

![Diversworld](docs/dw-logo-k.png "Diversworld Logo")

# Welcome to ContaoDiveclubBundle

This bundle provides several modules that allow dive clubs to manage equipment data. In a future version, booking and
managing dive courses will also be added.

## Features

- **Equipment**
    - Manage additional equipment such as suits, ABC equipment, and similar items.
- **Regulators**
    - Manage regulators, including their servicing history.
- **Diving Equipment**
    - Manage diving gear, including TÜV inspection dates. Options for adding offers from inspection companies are
      available. In a future version, it will also be possible to book a TÜV inspection directly.
- **Dive Courses**
    - Add information about dive courses, such as course content and requirements.
- **TÜV Inspections**
    - Manage offers for TÜV inspections. In the child table, individual items in a TÜV inspection can be added; for
      example, item name, cylinder size, price in net and gross amounts. The other price (net or gross) is automatically
      calculated based on the entered value. In a future version, it is planned to enable bookings for club members.
      Members can register their cylinders and book an inspection.

### The Regulator Module

The data for the manufacturers and models of the regulators are entered in files, allowing flexible customization of the
equipment used by a club.
There is a file for regulators (`regulator_data`). In this file, data for manufacturers and the models of the first and
second stages are stored.
The template content defines the array that is read into the module. The array contains data per manufacturer for first
and second stages:

The manufacturers are defined in the template `equipment_manufacturer.txt` as follows:

```
<?php
return [
'1' => 'Scubapro',
'2' => 'Aqualung',
'3' => 'Mares',
'4' => 'Oceanic',
'5' => 'Cressi',
]
```

The regulator models are defined in the file `regulator_data.txt`. The number corresponds to the manufacturer's index:

```
<?php
return [
//Manufacturer 1
'1' => [
  'regModel1st' => [
    '1' => 'MK11',
    '2' => 'MK15',
    '3' => 'MK17',
    '4' => 'MK25',
    ], // Modells for the first stage
  'regModel2nd' => [
    '1'  => 'R180',
    '2'  => 'R190',
    '3'  => 'G260',
    '4'  => 'R105',
    '5' => 'D420'
    ], // Modells for the second stage
],
//Manufacturer 2
  '2' => [
    'regModel1st' => [
      '1'  => '1',
      '2'  => '2',
      '3'  => '3',
    ],
    'regModel2nd' => [
      '1'  => '1',
      '2'  => '2',
      '3'  => '3',
    ],
],
```

### The Dive Courses Module

In the Dive Courses module, the data for a dive course can be entered. _(Development is ongoing and will be further
enhanced in upcoming releases.)_

### The TÜV Inspection Module

In the TÜV Inspection module, offers from inspection companies can be managed. An offer can be assigned to an event. In
the calendar, you'll need to specify that the calendar's events may include TÜV appointments.
Once this flag is set in the calendar, a TÜV offer can be assigned to an event.
If an offer is assigned to an event, the corresponding offer will automatically be associated with the linked event, and
vice versa.
There is a frontend module that allows the data of the offer to be displayed on the frontend. To do this, the frontend
module **Offer Details** must be included as a module on a page with the event reader.

### The Diving Equipment Module

In the Diving Equipment module, the dive cylinders owned by the club can be recorded. In the child table of the diving
equipment, individual inspection dates can be logged.
This makes it easier to track which cylinders need to be inspected and which still have a valid inspection.
There is a frontend module that allows the data of the diving equipment to be displayed on the frontend. To do this, the
frontend module **Diving Equipment List** must be added to a page.
In a future version, it is planned to enable bookings of a dive cylinder for a TÜV inspection directly via this
overview.

## The Equipment reservation Module

With the registration module, members of the diving clubs have the opportunity to reserve and borrow club equipment.
Members can reserve equipment in the frontend, and once it is picked up, the reservation is processed by the admin
responsible for issuing the equipment.
Each piece of equipment is assigned a status, making it possible to track whether an item is available or borrowed.
The following statuses can be assigned:

`available`
`reserved`
`borrowed`
`returned`
`canceled`
`overdue`
`lost`
`damaged`
`missing`

## The Dive Course Module

The diving course module can be used to manage diving courses. Diving courses can be created and the modules to be
completed during training can be assigned to each diving course. The exercises to be completed can be attached to each
module.
The diving courses are defined once in this way.
Diving students are managed in a second table. Each diving student can be assigned a course for which they have
registered. When a course is assigned, the
exercises to be completed are added automatically.
Instructors can now document each student's progress in the course by marking each completed exercise as done. An
exercise can also be marked as needing to be repeated or as failed.
The courses and course types can be defined in a text file, as with the equipment. This allows different courses to be
created depending on the association.

The text files can be created by the user. The name is irrelevant. The respective text files can then be assigned in the
settings (Configuration).

### Global Configuration
The bundle uses a central configuration table (`tl_dc_config`) where you can define:
- **Templates:** Assign your custom PHP files for manufacturers, equipment types, sizes, regulators, and courses.
- **Invoices:** Select a PDF template (stationery) and define an additional text for generated invoices.
- **Storage Locations:** Choose specific folders in the Contao file system for generated PDF invoices and TÜV lists. If no folder is selected, they will be saved in the `files/` directory.
- **TÜV List Export:** Choose the default export format (PDF, CSV, or XLSX).
- **Reservations:** Configure confirmation messages and email notification addresses for equipment reservations.
- **Rental Conditions:** Define the terms and conditions for equipment rental.

The manufacturers are defined in the template `dc_course_categories.txt` as follows:

```
<?php
return [
'try' => 'Schnuppertauchen',
'basic' => 'GDL Pool Diver (DTSA Grundtauchschein)',
'gdlsd' => 'GDL* Sports Diver (DTSA*)',
'gdlasd' => 'GDL** Advanced Sports Diver (DTSA**)',
'gdldl' => 'GDL*** Dive Leader (DTSA***)',
'gdldd' => 'GDL Deep Diver (SK Tiefer Tauchen)',
];
```

The manufacturers are defined in the template `dc_course_types.txt` as follows:

```
<?php
return [
'basic' => 'Grundkurs',
'specialty' => 'Spezialkurse',
'mixgas' => 'Mischgastauchen',
'professional' => 'Professionell'
];
```
## Tank Installation & TÜV List Export
In the backend, an existing offer can be created with the prices for an inspection. The individual items with the prices are stored as child elements of the offer. There are optional items and default items that are always included in the booking.

### TÜV List Export
The bundle allows exporting the list of equipment registered for a TÜV inspection.
- **Formats:** You can choose between PDF, CSV, and XLSX (Excel) in the global configuration.
- **Storage:** The generated lists are automatically saved in the configured folder (or `files/` by default).
- **Manual Export:** In the backend, you can trigger the export for a specific TÜV proposal.

In the frontend, the planned TÜV dates are displayed in a list with the diving courses.
Members can register their equipment for inspection. It is possible to register several pieces of diving equipment for inspection.
The member's booking can then be managed in the backend. Invoices can be generated as PDF and are automatically saved to the configured storage location.

## Own Insert Tags (Diveclub Bundle)
The bundle provides three main insert tags to display dynamic data from the current user session (e.g., after a booking or registration).
**Important:** These tags only work immediately after an action (booking/registration), as long as the corresponding ID is stored in the session.

### A) For Tank Checks (dc_check)
Accesses data from the `tl_dc_check_order` (tank) and `tl_dc_check_booking` (booking header) tables.
These tags refer to the data from the booking header, based on the ID in the session variable `last_tank_check_order`.

**Syntax:** `{{dc_check::property}}`

| Tag | Description |
| :--- | :--- |
| `{{dc_check::bookingNumber}}` | The generated booking number (e.g., TC-2026...). |
| `{{dc_check::totalPrice}}` | The total price of the booking. |
| `{{dc_check::serialNumber}}` | The serial number of the (first) tank. |
| `{{dc_check::firstname}}` | First name of the person booking. |
| `{{dc_check::lastname}}` | Last name of the person booking. |
| `{{dc_check::email}}` | Email address of the person booking. |
| `{{dc_check::bookingDate}}` | Date of the booking (formatted according to system settings). |
| `{{dc_check::notes}}` | Booking notes/remarks. |

### B) For Booking Details (booking)
Accesses data from the `tl_dc_check_booking` table.
These tags refer to the booking header, based on the ID in the session variable `last_tank_check_order` or from the request attributes.

**Syntax:** `{{booking::property}}`

| Tag | Description |
| :--- | :--- |
| `{{booking::bookingNumber}}` | The generated booking number. |
| `{{booking::totalPrice}}` | The total price of the booking (formatted with €). |
| `{{booking::firstname}}` | First name of the person booking. |
| `{{booking::lastname}}` | Last name of the person booking. |
| `{{booking::email}}` | Email address of the person booking. |
| `{{booking::bookingDate}}` | Date of the booking (formatted). |
| `{{booking::status}}` | Current status of the booking (translated). |
| `{{booking::paid}}` | Payment status (Yes/No, translated). |

### C) For Course Registrations (course)
Accesses data from the `tl_dc_course_students` (assignment), `tl_dc_students` (student), and `tl_dc_course_event` (course) tables.
These tags refer to the course assignment, the student, and the event, based on `last_course_order`.

**Syntax:** `{{course::property}}`

| Tag | Description |
| :--- | :--- |
| `{{course::title}}` | Name of the diving course / event. |
| `{{course::firstname}}` | First name of the student. |
| `{{course::lastname}}` | Last name of the student. |
| `{{course::email}}` | Email address of the student. |
| `{{course::price}}` | Course fee / price. |
| `{{course::dateStart}}` | Start date of the course (formatted). |
| `{{course::dateEnd}}` | End date of the course (formatted). |
| `{{course::registered_on}}` | Date of registration. |
| `{{course::status}}` | Current status of the course assignment (raw value). |

### Usage & Formatting

These insert tags can be used in all Contao text fields (content elements, email subjects, confirmation texts). All code and templates are documented with inline comments to explain the purpose of each instruction.

## Frontend-Module & Templates

The bundle provides various frontend modules. The presentation can be customized via Twig templates. You can find detailed documentation of the templates and their customization options in [TEMPLATES.md](TEMPLATES.md).
#### Date Values
Fields such as `bookingDate`, `dateStart`, `dateEnd`, or `registered_on` are automatically formatted based on the date format (`datimFormat`) defined in the Contao system settings.
#### Standard Flags
Since these tags utilize the modern Contao 5 system, they can be combined with standard flags:
- `{{dc_check::totalPrice|number_format:2}}`
- `{{course::title|strtoupper}}`
## Future Plans

- Automatic notification when course status changes
- Automatic notification when equipment checks are needed
- Members will be able to record their own equipment.

### [Weitere Informationen im WIKI](https://github.com/EckhardBecker/Diversworld_DiveClubManager/wiki)

## Donation

If you like this extension and think it's worth a little donation: You can support me via Paypal.Me:

[Donation for Diversworld DiveClubManager](https://paypal.me/EckhardBecker615)

Thank You!
