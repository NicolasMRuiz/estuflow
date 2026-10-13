<?php

namespace Tests;

use App\Academic\AcademicProgress;
use App\Academic\CatalogImporter;
use App\Models\AcademicEnrollment;
use App\Models\StudyPlan;
use App\Models\User;
use App\Support\TestingDatabase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        TestingDatabase::assertSafe();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.(crc32($this->name()) % 250 + 1)]);
    }

    protected function configureAcademic(User $user): AcademicEnrollment
    {
        app(CatalogImporter::class)->file();
        $progress = app(AcademicProgress::class);
        $enrollment = $progress->select($user, StudyPlan::where('key', 'TSAS-6790-19-INGRESO-2020')->sole()->id);
        $progress->finish($user);

        return $enrollment->fresh(['plan.courses', 'plan.yearRules', 'progress']);
    }
}
