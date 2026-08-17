<?php

namespace App\Observers;

use App\Models\VolunteerCampaign;

class VolunteerCampaignObserver
{
    public function created(VolunteerCampaign $volunteerCampaign): void
    {
        // لما يضيف متطوع جديد
        $campaign = $volunteerCampaign->campaign;
        
        if ($volunteerCampaign->status === 'approved') {
            $campaign->increment('volunteers_joined');
        }
    }

    public function updated(VolunteerCampaign $volunteerCampaign): void
    {
        // لما يعدّل status المتطوع
        $campaign = $volunteerCampaign->campaign;
        $originalStatus = $volunteerCampaign->getOriginal('status');
        $newStatus = $volunteerCampaign->status;

        // لو تغيّر من مش approved لـ approved
        if ($originalStatus !== 'approved' && $newStatus === 'approved') {
            $campaign->increment('volunteers_joined');
        }
        // لو تغيّر من approved لـ مش approved
        elseif ($originalStatus === 'approved' && $newStatus !== 'approved') {
            $campaign->decrement('volunteers_joined');
        }
    }

    public function deleted(VolunteerCampaign $volunteerCampaign): void
    {
        // لما يحذف متطوع
        $campaign = $volunteerCampaign->campaign;
        
        if ($volunteerCampaign->status === 'approved') {
            $campaign->decrement('volunteers_joined');
        }
    }
}