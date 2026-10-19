<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PendingAssignment = 'pending_assignment';
    case AwaitingUnitClaim = 'awaiting_unit_claim';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingCustomer = 'waiting_customer';
    case PendingFinalApproval = 'pending_final_approval';
    case Approved = 'approved';
    case Closed = 'closed';
    case Archived = 'archived';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::PendingAssignment => 'در انتظار تخصیص',
            self::AwaitingUnitClaim => 'در انتظار پذیرش واحد',
            self::Assigned => 'ارجاع شده',
            self::InProgress => 'در حال رسیدگی',
            self::WaitingCustomer => 'منتظر مشتری',
            self::PendingFinalApproval => 'منتظر تأیید نهایی',
            self::Approved => 'تأیید شده',
            self::Closed => 'بسته شده',
            self::Archived => 'بایگانی شده',
            self::Reopened => 'بازگشایی شده',
        };
    }

    public function publicLabel(): string
    {
        return match ($this) {
            self::PendingAssignment, self::AwaitingUnitClaim, self::Assigned => 'ثبت شده و در صف بررسی',
            self::InProgress, self::Reopened => 'در حال بررسی',
            self::WaitingCustomer => 'نیاز به مدرک یا اطلاعات تکمیلی',
            self::PendingFinalApproval => 'در انتظار تأیید نهایی',
            self::Approved, self::Closed => 'رسیدگی شده',
            self::Archived => 'بایگانی شده',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Archived], true);
    }

    public function allowsNullOwner(): bool
    {
        return in_array($this, [
            self::PendingAssignment,
            self::AwaitingUnitClaim,
            self::PendingFinalApproval,
            self::Approved,
            self::Closed,
            self::Archived,
        ], true);
    }

    /**
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::PendingAssignment => [self::Assigned, self::AwaitingUnitClaim],
            self::AwaitingUnitClaim => [self::Assigned],
            self::Assigned => [self::InProgress, self::Assigned, self::AwaitingUnitClaim],
            self::InProgress => [self::WaitingCustomer, self::PendingFinalApproval, self::Assigned, self::AwaitingUnitClaim],
            self::WaitingCustomer => [self::InProgress, self::Assigned, self::AwaitingUnitClaim],
            self::PendingFinalApproval => [self::Approved, self::Assigned, self::AwaitingUnitClaim],
            self::Approved => [self::Closed],
            self::Closed => [self::Archived, self::Reopened],
            self::Archived => [self::Reopened],
            self::Reopened => [self::Assigned, self::AwaitingUnitClaim],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->transitions(), true);
    }
}
