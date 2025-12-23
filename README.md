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
[
'1' => 'Scubapro',
'2' => 'Aqualung',
'3' => 'Mares',
'4' => 'Oceanic',
'5' => 'Cressi',
]
```

The regulator models are defined in the file `regulator_data.txt`. The number corresponds to the manufacturer's index:

```
[
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
settings.

The manufacturers are defined in the template `dc_course_categories.txt` as follows:

```
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
return [
'basic' => 'Grundkurs',
'specialty' => 'Spezialkurse',
'mixgas' => 'Mischgastauchen',
'professional' => 'Professionell'
];
```

## Future Plans

- Members will be able to record their own equipment.
- Members will be able to book TÜV inspections for their equipment.
- Club-owned rental equipment will be available for reservation by members.
- Interested people will be able to register for dive courses.
- Club instructors will be able to manage student data and course information related to students.

## Donation

If you like this extension and think it's worth a little donation: You can support me via Paypal.Me:

[Donation for Diversworld CalendarEditor](https://paypal.me/EckhardBecker615)

Thank You!
