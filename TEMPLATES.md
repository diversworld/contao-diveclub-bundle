# Twig Templates and Customization Options

The `ContaoDiveclubBundle` uses modern Contao 5 Twig templates. The presentation of the frontend modules can be flexibly customized via Twig template inheritance.

## General Customization via Inheritance

To customize a template, create a new file in the Contao `templates/` directory (e.g., `mod_dc_course_events_list_custom.html.twig`) and inherit from the original template. Thanks to the flat block structure, you can overwrite specific areas.

**Example:**
```twig
{% extends "@Contao/frontend_module/mod_dc_course_events_list" %}

{% block event_title %}
    <h3>Exclusive: {{ ev.title }}</h3>
{% endblock %}
```

---

## Overview of Templates

### Course Events List (`dc_course_events_list`)
Shows an overview of all published course events.

**Template:** `mod_dc_course_events_list.html.twig`

**Available Variables:**
- `events`: Array of events to be displayed. Each element contains:
    - `id`: Internal ID.
    - `title`: Title of the event.
    - `dateStart`: Formatted start date.
    - `dateEnd`: Formatted end date.
    - `instructor`: Name of the instructor.
    - `description`: Description (HTML).
    - `location`: Event location.
    - `maxParticipants`: Maximum number of participants.
    - `price`: Course fee.
    - `url`: Link to the detail page.
    - `isTankCheck`: (Optional) `true` if it is a tank check (TÜV) appointment.
- `hasEvents`: Boolean, whether events are available.
- `hasJumpTo`: Boolean, whether a reader page is configured.

**Important Blocks:**
- `events_list`: Container for the list.
- `event_item`: A single list entry.
- `event_title`: The title of the event.
- `event_details`: Detailed information (date, instructor, location).
- `event_description`: Short description.
- `event_link`: Link to the detail page.

---

### Course Event Reader (`dc_course_event_reader`)
Shows the details of a single course event including the schedule and registration form.

**Template:** `mod_dc_course_event_reader.html.twig`

**Available Variables:**
- `event`: Object/array with the details of the event:
    - `title`, `description`, `dateStart`, `dateEnd`, `price`, `instructor`.
- `schedule`: Array with the appointments of the schedule:
    - `planned_at`: Formatted date/time.
    - `location`, `instructor`, `notes`, `module`, `exercise`.
- `hasSchedule`: Boolean, whether a schedule exists.
- `isLoggedIn`: Boolean, whether the user is logged in.
- `alreadyRegistered`: Boolean, whether the user is already registered.
- `signup`: Array with labels for the registration form.
- `request_token`: CSRF token for the form.

**Important Blocks:**
- `event_title`: Title of the event.
- `event_details`: Basic information (start, end, price, instructor).
- `event_description`: Detailed description.
- `event_schedule`: The entire schedule area.
- `schedule_table`: The table with the appointments.
- `event_signup`: The registration area.
- `signup_guest`: The form for guests.
- `signup_member`: The form for logged-in members.

---

### Course Schedule / Calendar (`dc_course_event_calendar`)
Displays the appointments of an event in a calendar view.

**Template:** `mod_dc_course_event_calendar.html.twig`

**Available Variables:**
- `weeks`: Array of weeks, which in turn contain arrays of days:
    - `label`: Day of the month.
    - `events`: Array of appointments on this day (`title`, `time`, `location`, `instructor`, `notes`).
    - `class`: CSS class for the day (`today`, `weekend`, `empty`).
- `days`: Array of weekday names.
- `currentMonth`: Name of the currently displayed month and year.
- `prevHref` / `nextHref`: Links for navigation.
- `hasEvents`: Boolean, whether appointments are available.

**Important Blocks:**
- `calendar_table`: The table container.
- `calendar_nav`: Prev/Next navigation.
- `calendar_day_labels`: Weekday names.
- `calendar_event`: A single appointment in the calendar.
- `calendar_event_details`: Detail popup when clicking on an appointment.

---

### Tank Check / Cylinder Inspection (`dc_tank_check`)
Allows members and guests to register diving cylinders for a TÜV inspection.

**Template:** `mod_dc_tank_check.html.twig`

**Available Variables:**
- `proposals`: List of available tank check appointments (for the list view).
- `isBooking`: Boolean, whether a booking is currently being performed.
- `proposal`: The currently selected tank check offer.
- `sessionTanks`: Array of cylinders already in the "shopping cart".
- `articles`: List of additionally bookable services (O2 service, etc.).
- `tankSizes`: Array of available cylinder sizes.
- `labels`: Translations for the form.
- `success`: Boolean, whether the booking was successfully completed.

**Important Blocks:**
- `proposal_list`: List of available tank check appointments.
- `booking_view`: The view during the booking process.
- `form_add_tank`: Form for adding a cylinder.
- `reserved_tanks`: Overview of cylinders already noted.
- `final_booking`: Completion of the booking (address data).
- `success_view`: Confirmation page after successful booking.

---

### Course Progress (`dc_course_progress`)
Shows the current training status of a diving student.

**Template:** `mod_dc_course_progress.html.twig`

**Available Variables:**
- `assignment`: Information on the course assignment (`id`, `status`, `course_title`).
- `exercises`: List of exercises:
    - `title`, `module`, `status`, `status_label`, `instructor`, `dateCompleted`.
- `schedule`: Schedule of the associated event.
- `labels`: Translations for the view.

**Important Blocks:**
- `course_info`: Information on the selected course.
- `progress_summary`: Summary of completed exercises.
- `modules_list`: List of course modules.
- `exercise_row`: A single exercise with status.

---

### Other Templates

#### Booking Overview (`dc_booking`)
**Template:** `mod_dc_booking.html.twig`
- `items`: List of bookings.

#### Equipment Listing (`dc_equipment_listing`)
**Template:** `mod_dc_equipment_listing.html.twig`
- `data`: Array with equipment types (`id`, `title`, `type`).

#### Club Cylinders (`dc_tanks_listing`)
**Template:** `mod_dc_tanks_listing.html.twig`
- `tanks`: Array with cylinder data (serial number, size, TÜV date, etc.).

#### Student Course Overview (`dc_student_courses`)
**Template:** `mod_dc_student_courses.html.twig`
- `courses`: List of courses of a student.

#### General List (`dc_listing`)
**Template:** `mod_dc_listing.html.twig`
- `event`, `proposal`, `articles`: Linked data of an event.
