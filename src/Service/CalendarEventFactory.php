<?php

namespace App\Service;

use App\Entity\BlockoutDate;
use App\Entity\Booking;

/**
 * Builds FullCalendar event payloads for the admin and staff portals.
 *
 * Keeping this in one place guarantees the admin record-management calendar
 * and the staff dashboard calendar always display the same data and colours.
 */
class CalendarEventFactory
{
    /**
     * @param Booking[]      $bookings
     * @param BlockoutDate[] $blockouts
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(array $bookings, array $blockouts): array
    {
        $events = [];

        foreach ($bookings as $booking) {
            if ($booking->getBookingStatus() === Booking::STATUS_CANCELLED) {
                continue;
            }

            $date = $booking->getScheduleDate();
            $time = $booking->getScheduleTime();
            if (!$date || !$time) {
                continue;
            }

            $isConfirmed = $booking->getBookingStatus() === Booking::STATUS_CONFIRMED;
            $customer = $booking->getCustomer();
            $package = $booking->getPackage();
            $creator = $booking->getAdmin();

            $events[] = [
                'title' => ($customer?->getFullName() ?: 'Client') . ' - ' . ($package?->getName() ?? 'Studio Package'),
                'start' => $date->format('Y-m-d') . 'T' . $time->format('H:i:s'),
                'backgroundColor' => $isConfirmed ? '#ecfdf5' : '#fffbe2',
                'borderColor' => $isConfirmed ? '#a7f3d0' : '#fde68a',
                'textColor' => $isConfirmed ? '#065f46' : '#92400e',
                'extendedProps' => [
                    'statusBadge' => $isConfirmed ? 'Confirmed' : 'Pending',
                    'raw' => [
                        'id' => $booking->getBookingId(),
                        'ref' => $booking->getDisplayReference(),
                        'name' => $customer?->getFullName() ?: 'Client',
                        'email' => $customer?->getEmail() ?: 'N/A',
                        'phone' => $customer?->getPhone() ?: 'N/A',
                        'package' => $package?->getName() ?? 'Studio Package',
                        'price' => number_format((float) ($package?->getPrice() ?? 0), 0),
                        'date' => $date->format('M d, Y'),
                        'time' => $time->format('g:i A'),
                        'notes' => $booking->getNotes() ?: 'None',
                        'proof' => $booking->getPaymentProofUrl(),
                        'status' => $booking->getBookingStatus(),
                        'creatorId' => $creator?->getAdminId(),
                        'creator' => $creator?->getFullName() ?: 'Client self-booking',
                        'creatorRole' => $creator?->getRoleLabel() ?? 'Client',
                    ],
                ],
            ];
        }

        foreach ($blockouts as $blockout) {
            $date = $blockout->getBlockoutDate();
            if (!$date) {
                continue;
            }

            $startTime = $blockout->getStartTime();
            $endTime = $blockout->getEndTime();

            if ($startTime && $endTime) {
                $start = $date->format('Y-m-d') . 'T' . $startTime->format('H:i:s');
                $end = $date->format('Y-m-d') . 'T' . $endTime->format('H:i:s');
                $allDay = false;
            } else {
                $start = $date->format('Y-m-d');
                $end = $date->format('Y-m-d');
                $allDay = true;
            }

            $events[] = [
                'title' => 'BLOCKED: ' . ($blockout->getReason() ?: 'Unavailable'),
                'start' => $start,
                'end' => $end,
                'allDay' => $allDay,
                'backgroundColor' => '#fef2f2',
                'borderColor' => '#fecaca',
                'textColor' => '#991b1b',
                'extendedProps' => [
                    'statusBadge' => 'Blockout',
                    'raw' => null,
                ],
            ];
        }

        return $events;
    }
}
