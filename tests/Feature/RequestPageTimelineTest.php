<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Filament\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Support\RequestTimeline;
use App\Models\ServiceRequest;
use App\Services\AssignmentService;
use App\Services\RequestMessageService;
use App\Services\RequestViewService;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\CrmTestCase;

class RequestPageTimelineTest extends CrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_page_shows_header_tabs_and_merged_timeline(): void
    {
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert', ['name' => 'کارشناس تایم‌لاین']);
        $request = ServiceRequest::factory()->create(['product_name' => 'بشقاب چینی']);
        app(AssignmentService::class)->assignInitial($request, $admin, $expert->id, null, 'بررسی سریع شود', 'شروع کار');
        app(RequestViewService::class)->record($request->refresh(), $expert);
        app(RequestMessageService::class)->addExpertNote($request->refresh(), $expert, 'کالا شکسته رسیده است');
        app(RequestMessageService::class)->addAdminNote($request->refresh(), $admin, 'یادداشت محرمانهٔ مدیریت');

        $this->actingAs($expert);
        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])
            ->assertOk()
            ->assertSee('پرونده '.$request->tracking_code)
            ->assertSee(['تایم‌لاین', 'اطلاعات', 'ارجاع‌ها', 'پیام‌ها', 'مدارک', 'تأیید و مشاهده'])
            ->assertSee(['ثبت درخواست', 'رسیدگی کارشناس', 'بسته شدن'])
            ->assertSee('درخواست ثبت شد')
            ->assertSee('تخصیص اولیه به کارشناس تایم‌لاین')
            ->assertSee('بررسی سریع شود')
            ->assertSee('کالا شکسته رسیده است')
            ->assertDontSee('یادداشت محرمانهٔ مدیریت');

        $this->actingAs($admin);
        Livewire::test(ViewServiceRequest::class, ['record' => $request->id])
            ->assertSee('یادداشت محرمانهٔ مدیریت');
    }

    public function test_timeline_is_newest_first_and_stepper_tracks_status(): void
    {
        $admin = $this->staff('system_administrator');
        $expert = $this->staff('expert');
        $request = ServiceRequest::factory()->create(['submitted_at' => now()->subDay()]);
        app(AssignmentService::class)->assignInitial($request, $admin, $expert->id, null, null, 'شروع');

        $events = RequestTimeline::events($request->refresh(), $admin);

        $this->assertSame('submitted', $events->last()['kind']);
        $this->assertTrue($events->first()['at']->greaterThanOrEqualTo($events->last()['at']));

        $states = array_column(RequestTimeline::steps(RequestStatus::InProgress), 'state');
        $this->assertSame(['done', 'done', 'current', 'upcoming', 'upcoming'], $states);
        $this->assertSame(['done', 'done', 'done', 'done', 'done'], array_column(RequestTimeline::steps(RequestStatus::Closed), 'state'));
    }
}
