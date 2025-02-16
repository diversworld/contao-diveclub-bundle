![Diversworld](docs/dw-logo-k.png "Diversworld Logo")


# Welcome to ContaoDiveclubBundle

This bundle provides several modules that allow dive clubs to manage equipment data. In a future version, booking and managing dive courses will also be added.

## Features
- **Equipment**
  - Manage additional equipment such as suits, ABC equipment, and similar items.
- **Regulators**
  - Manage regulators, including their servicing history.
- **Diving Equipment**
  - Manage diving gear, including TÜV inspection dates. Options for adding offers from inspection companies are available. In a future version, it will also be possible to book a TÜV inspection directly.
- **Dive Courses**
  - Add information about dive courses, such as course content and requirements.
- **TÜV Inspections**
  - Manage offers for TÜV inspections. In the child table, individual items in a TÜV inspection can be added; for example, item name, cylinder size, price in net and gross amounts. The other price (net or gross) is automatically calculated based on the entered value. In a future version, it is planned to enable bookings for club members. Members can register their cylinders and book an inspection.

### The Regulator Module
The data for the manufacturers and models of the regulators are entered in template files, allowing flexible customization of the equipment used by a club.
There is a template for regulators (`regulator_data`). In this template, data for manufacturers and the models of the first and second stages are stored.
The template content defines the array that is read into the module. The array contains data per manufacturer for first and second stages:

The manufacturers are defined in the template `equipment_manufacturer.html5` as follows:

```
[
'1' => 'Scubapro',
'2' => 'Aqualung',
'3' => 'Mares',
'4' => 'Oceanic',
'5' => 'Cressi',
]
```
The regulator models are defined in the template `regulator_data.html5`. The number corresponds to the manufacturer's index:
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
In the Dive Courses module, the data for a dive course can be entered. _(Development is ongoing and will be further enhanced in upcoming releases.)_

### The TÜV Inspection Module
In the TÜV Inspection module, offers from inspection companies can be managed. An offer can be assigned to an event. In the calendar, you'll need to specify that the calendar's events may include TÜV appointments. Once this flag is set in the calendar, a TÜV offer can be assigned to an event.
If an offer is assigned to an event, the corresponding offer will automatically be associated with the linked event, and vice versa.
There is a frontend module that allows the data of the offer to be displayed on the frontend. To do this, the frontend module **Offer Details** must be included as a module on a page with the event reader.

### The Diving Equipment Module
In the Diving Equipment module, the dive cylinders owned by the club can be recorded. In the child table of the diving equipment, individual inspection dates can be logged. This makes it easier to track which cylinders need to be inspected and which still have a valid inspection.
There is a frontend module that allows the data of the diving equipment to be displayed on the frontend. To do this, the frontend module **Diving Equipment List** must be added to a page.
In a future version, it is planned to enable bookings of a dive cylinder for a TÜV inspection directly via this overview.

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
