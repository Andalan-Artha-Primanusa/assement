<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrPackageSeeder extends Seeder
{
    public const HR_DISPATCH_PLANT = 'Screening HR Dispatch Plant';
    public const HR_DISPATCHER_MCC = 'Screening HR Dispatcher MCC';
    public const HR_ADMIN_FINANCE = 'Screening HR Admin Finance';
    public const HR_ADMIN_ACCOUNTING = 'Screening HR Admin Accounting';
    public const HR_ADMIN_SHE = 'Screening HR Admin SHE';
    public const HR_ADMIN_HRGA = 'Screening HR Admin HRGA';
    public const HR_ADMIN_OPERATION = 'Screening HR Admin Operation';
    public const HR_ADMIN_ENGINEERING = 'Screening HR Admin Engineering';
    public const HR_ADMIN_GENERAL = 'Screening HR Admin General';

    public const PACKAGES = [
        'Dispatch Plant' => self::HR_DISPATCH_PLANT,
        'Dispatcher MCC' => self::HR_DISPATCHER_MCC,
        'Admin Finance' => self::HR_ADMIN_FINANCE,
        'Admin Accounting' => self::HR_ADMIN_ACCOUNTING,
        'Admin SHE' => self::HR_ADMIN_SHE,
        'Admin HRGA' => self::HR_ADMIN_HRGA,
        'Admin Operation' => self::HR_ADMIN_OPERATION,
        'Admin Engineering' => self::HR_ADMIN_ENGINEERING,
        'Admin General' => self::HR_ADMIN_GENERAL,
    ];

    public function run(): void
    {
        $adminHr = User::where('email', 'admin.hr@andalan.co.id')->first();

        foreach (self::PACKAGES as $level => $name) {
            QuestionPackage::updateOrCreate(
                ['name' => $name],
                [
                    'description' => 'Paket screening HR untuk posisi '.$level.'.',
                    'type' => QuestionPackage::TYPE_HR,
                    'level' => $level,
                    'is_active' => true,
                    'has_segments' => false,
                    'created_by' => $adminHr?->id,
                    'min_score_pertimbangan' => 60,
                    'min_score_lolos' => 70,
                ]
            );
        }
    }
}
