<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * ComprehensiveDemoSeeder — populates ~70 "module" tables with realistic
 * Arabic/English demo data so every dashboard & list page has content to show.
 *
 * Safe to re-run: every block skips if target table already has rows.
 */
class ComprehensiveDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    protected array $firstNamesEn = ['Ahmed','Mohammed','Omar','Khalid','Fahad','Yousef','Saleh','Abdullah','Majed','Bandar','Talal','Saad','Rayan','Faisal','Hassan','Sarah','Noura','Lina','Reem','Mona','Aisha','Huda','Nouf','Rania','Layla','Dana','Fatima','Maya','Amal','Ghada'];
    protected array $lastNamesEn = ['Al-Shehri','Al-Qahtani','Al-Harbi','Al-Otaibi','Al-Dossari','Al-Zahrani','Al-Mutairi','Al-Anazi','Al-Malki','Al-Subaie','Al-Juhani','Al-Ghamdi','Al-Shammari','Al-Asiri','Al-Rashidi'];
    protected array $sentences = [
        'Patient tolerated the procedure well with no adverse effects.',
        'Follow-up advised in two weeks to monitor progress.',
        'Excellent response to treatment — continue current regimen.',
        'Further evaluation recommended to rule out complications.',
        'Home care instructions reviewed with the patient.',
        'Patient satisfied with the explanation and proposed plan.',
        'Minor discomfort reported, expected to resolve within 48 hours.',
        'All vital signs stable; discharge with routine instructions.',
    ];
    protected array $words = ['routine','standard','elective','scheduled','preventive','diagnostic','therapeutic','cosmetic','restorative'];
    protected array $streetsAr = ['شارع الملك فهد','شارع التحلية','شارع العليا','شارع الأمير سلطان','شارع الستين','طريق الملك عبدالعزيز'];
    protected array $citiesAr = ['الرياض','جدة','الدمام','مكة المكرمة','المدينة المنورة','الخبر','الطائف','أبها'];

    protected array $patientIds = [];
    protected array $childPatientIds = [];
    protected array $doctorIds = [];
    protected array $visitIds = [];
    protected array $invoiceIds = [];
    protected array $bookingIds = [];
    protected array $staffUserIds = [];
    protected int $adminUserId;

    public function run(): void
    {
        // Pull reference IDs once
        $this->patientIds     = DB::table('patients')->pluck('id')->all();
        $this->doctorIds      = DB::table('doctors')->pluck('id')->all();
        $this->visitIds       = DB::table('visits')->pluck('id')->all();
        $this->invoiceIds     = DB::table('invoices')->pluck('id')->all();
        $this->bookingIds     = DB::table('bookings')->pluck('id')->all();
        $this->adminUserId    = (int) (DB::table('users')->where('email', 'admin@aura.com')->value('id') ?? DB::table('users')->value('id'));
        $this->staffUserIds   = DB::table('users')->whereIn('role_id', [2,3,5,6,8,9])->pluck('id')->all();

        // Infer child patients by birth date (age < 14)
        foreach (DB::table('patients')->select('id','date_of_birth')->get() as $p) {
            if ($p->date_of_birth && Carbon::parse($p->date_of_birth)->age < 14) {
                $this->childPatientIds[] = $p->id;
            }
        }

        if (empty($this->patientIds) || empty($this->doctorIds)) {
            $this->command->warn('Skipping ComprehensiveDemoSeeder: patients/doctors not seeded yet.');
            return;
        }

        $this->command->info('═══ ComprehensiveDemoSeeder ═══');

        $this->runGroup('Insurance',        fn() => $this->seedInsurance());
        $this->runGroup('Cosmetic',         fn() => $this->seedCosmetic());
        $this->runGroup('Dermatology',      fn() => $this->seedDermatology());
        $this->runGroup('Dental extras',    fn() => $this->seedDentalExtras());
        $this->runGroup('Pediatric extras', fn() => $this->seedPediatric());
        $this->runGroup('HR',               fn() => $this->seedHr());
        $this->runGroup('Inventory',        fn() => $this->seedInventory());
        $this->runGroup('CRM extras',       fn() => $this->seedCrm());
        $this->runGroup('Patient extras',   fn() => $this->seedPatientExtras());
        $this->runGroup('Other',            fn() => $this->seedOther());

        $this->command->info('═══ ComprehensiveDemoSeeder done ═══');
    }

    protected function runGroup(string $label, \Closure $fn): void
    {
        try {
            DB::transaction(fn() => $fn());
            $this->command->info("  ✓ {$label} group seeded");
        } catch (\Throwable $e) {
            $this->command->error("  ✗ {$label} failed: " . $e->getMessage());
        }
    }

    protected function info(string $table, int $n): void
    {
        $this->command->info("    + {$n} rows in {$table}");
    }

    protected function pick(array $arr) { return $arr[array_rand($arr)]; }
    protected function firstName(): string { return $this->pick($this->firstNamesEn); }
    protected function lastName(): string { return $this->pick($this->lastNamesEn); }
    protected function fullName(): string { return $this->firstName().' '.$this->lastName(); }
    protected function sentence(): string { return $this->pick($this->sentences); }
    protected function word(): string { return $this->pick($this->words); }
    protected function addressAr(): string { return $this->pick($this->streetsAr).'، '.$this->pick($this->citiesAr); }
    protected function ipv4(): string { return random_int(10,250).'.'.random_int(0,255).'.'.random_int(0,255).'.'.random_int(1,254); }
    protected function bothify(string $pattern): string {
        $out = '';
        $letters = range('A','Z');
        foreach (str_split($pattern) as $c) {
            if ($c === '#') $out .= random_int(0,9);
            elseif ($c === '?') $out .= $letters[array_rand($letters)];
            else $out .= $c;
        }
        return $out;
    }
    protected function creditCard(): string { return implode('-', [random_int(1000,9999), random_int(1000,9999), random_int(1000,9999), random_int(1000,9999)]); }
    protected function maybe(array $arr, ?int $n = null) {
        $n = $n ?? random_int(1, count($arr));
        shuffle($arr);
        return array_slice($arr, 0, $n);
    }

    // ── Insurance ────────────────────────────────────────────────────
    protected function seedInsurance(): void
    {
        if (DB::table('insurance_companies')->count() === 0) {
            $companies = [
                ['name_ar'=>'بوبا العربية','name_en'=>'Bupa Arabia','code'=>'BUPA','phone'=>'+966920000810','email'=>'info@bupa.com.sa','contact_person'=>'Ahmed Al-Shehri'],
                ['name_ar'=>'التعاونية','name_en'=>'Tawuniya','code'=>'TAW','phone'=>'+966920019990','email'=>'info@tawuniya.com.sa','contact_person'=>'Sarah Al-Qahtani'],
                ['name_ar'=>'متلايف','name_en'=>'MetLife','code'=>'METL','phone'=>'+96611201066','email'=>'info@metlife.com.sa','contact_person'=>'Omar Al-Harbi'],
                ['name_ar'=>'أكسا للتأمين','name_en'=>'AXA Cooperative','code'=>'AXA','phone'=>'+966920027777','email'=>'info@axa.com.sa','contact_person'=>'Laila Al-Otaibi'],
                ['name_ar'=>'الراجحي تكافل','name_en'=>'AlRajhi Takaful','code'=>'RAJHI','phone'=>'+966920007771','email'=>'info@alrajhitakaful.com','contact_person'=>'Khalid Al-Zahrani'],
                ['name_ar'=>'سلامة','name_en'=>'Salama Insurance','code'=>'SALM','phone'=>'+966920012020','email'=>'care@salama.com.sa','contact_person'=>'Nora Al-Dossari'],
                ['name_ar'=>'ميدغلف','name_en'=>'MedGulf','code'=>'MEDG','phone'=>'+966920025555','email'=>'info@medgulf.com.sa','contact_person'=>'Faisal Al-Mutairi'],
            ];
            $rows = [];
            foreach ($companies as $c) {
                $rows[] = array_merge($c, [
                    'logo'=>null,'address'=>$this->addressAr(),'notes'=>null,'is_active'=>1,
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
            DB::table('insurance_companies')->insert($rows);
            $this->info('insurance_companies', count($rows));
        }

        if (DB::table('insurance_plans')->count() === 0) {
            $planNames = [
                ['VIP','Premium Plus','بريميوم بلس'],
                ['A','Executive','تنفيذي'],
                ['A','Gold','ذهبي'],
                ['B','Silver','فضي'],
                ['C','Standard','قياسي'],
                ['D','Basic','أساسي'],
            ];
            $rows = [];
            foreach (DB::table('insurance_companies')->get() as $co) {
                foreach (array_slice($planNames, 0, random_int(3,5)) as $p) {
                    $rows[] = [
                        'insurance_company_id'=>$co->id,
                        'name_ar'=>$co->name_ar.' - '.$p[2],
                        'name_en'=>$co->name_en.' '.$p[1],
                        'plan_code'=>$co->code.'-'.$p[0].'-'.random_int(100,999),
                        'class'=>$p[0],
                        'coverage_percentage'=>$p[0]==='VIP'?100:($p[0]==='A'?90:($p[0]==='B'?80:($p[0]==='C'?70:60))),
                        'max_coverage_amount'=>$p[0]==='VIP'?1000000:($p[0]==='A'?500000:200000),
                        'copay_amount'=>$p[0]==='VIP'?0:random_int(20,100),
                        'deductible'=>random_int(0,500),
                        'covers_dental'=>in_array($p[0],['VIP','A'])?1:(int)(random_int(0,1)),
                        'covers_dermatology'=>1,
                        'covers_cosmetic'=>$p[0]==='VIP'?1:0,
                        'covers_lab'=>1,'covers_xray'=>1,'covers_medication'=>1,
                        'exclusions'=>'Pre-existing cosmetic procedures, experimental treatments',
                        'notes'=>null,'is_active'=>1,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            DB::table('insurance_plans')->insert($rows);
            $this->info('insurance_plans', count($rows));
        }

        if (DB::table('patient_insurances')->count() === 0) {
            $plans = DB::table('insurance_plans')->get();
            $rows = [];
            $selectedPatients = array_slice($this->patientIds, 0, (int) ceil(count($this->patientIds) * 0.65));
            foreach ($selectedPatients as $pid) {
                $plan = $plans->random();
                $start = Carbon::now()->subMonths(random_int(1, 11));
                $rows[] = [
                    'patient_id'=>$pid,
                    'insurance_company_id'=>$plan->insurance_company_id,
                    'insurance_plan_id'=>$plan->id,
                    'member_id'=>'M-'.strtoupper(substr(md5((string)$pid),0,8)),
                    'policy_number'=>'POL-'.date('Y').'-'.str_pad((string)$pid, 6, '0', STR_PAD_LEFT),
                    'card_number'=>$this->creditCard(),
                    'start_date'=>$start->toDateString(),
                    'expiry_date'=>$start->copy()->addYear()->toDateString(),
                    'relationship'=>$this->pick(['self','self','self','spouse','child']),
                    'principal_name'=>null,
                    'max_annual_limit'=>$plan->max_coverage_amount,
                    'used_amount'=>random_int(0, (int)($plan->max_coverage_amount/20)),
                    'card_image_front'=>null,'card_image_back'=>null,
                    'notes'=>null,'is_active'=>1,
                    'is_verified'=>(int)(random_int(0,1)),
                    'verified_at'=>random_int(0,1) ? now() : null,
                    'verified_by'=>$this->adminUserId,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('patient_insurances')->insert($rows);
            $this->info('patient_insurances', count($rows));
        }

        if (DB::table('insurance_claims')->count() === 0) {
            $pis = DB::table('patient_insurances')->get();
            $statuses = ['draft','submitted','under_review','approved','partially_approved','rejected','paid','partially_paid'];
            $rows = [];
            for ($i = 0; $i < 15; $i++) {
                $pi = $pis->random();
                $total = random_int(500, 5000);
                $covered = (int)($total * 0.8);
                $status = $this->pick($statuses);
                $serviceDate = Carbon::now()->subDays(random_int(1, 90));
                $rows[] = [
                    'claim_number'=>'CLM-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'patient_insurance_id'=>$pi->id,
                    'patient_id'=>$pi->patient_id,
                    'invoice_id'=>$this->invoiceIds ? $this->pick($this->invoiceIds) : null,
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'service_date'=>$serviceDate->toDateString(),
                    'diagnosis'=>$this->pick(['Dermatitis contact','Acne vulgaris','Routine dental cleaning','Dental caries','Skin infection']),
                    'services_description'=>'Consultation + procedure as per itemized invoice',
                    'total_amount'=>$total,
                    'covered_amount'=>$covered,
                    'patient_share'=>$total - $covered,
                    'approved_amount'=>in_array($status,['approved','paid','partially_approved','partially_paid']) ? $covered : null,
                    'paid_amount'=>in_array($status,['paid']) ? $covered : (in_array($status,['partially_paid']) ? (int)($covered*0.6) : 0),
                    'status'=>$status,
                    'submitted_at'=>$status!=='draft' ? $serviceDate->copy()->addDays(1)->toDateString() : null,
                    'approved_at'=>in_array($status,['approved','paid','partially_approved','partially_paid']) ? $serviceDate->copy()->addDays(5)->toDateString() : null,
                    'paid_at'=>in_array($status,['paid','partially_paid']) ? $serviceDate->copy()->addDays(20)->toDateString() : null,
                    'rejection_reason'=>$status==='rejected' ? 'Service not covered under current plan' : null,
                    'notes'=>null,
                    'reference_number'=>'REF-'.strtoupper($this->bothify('??###')),
                    'created_by'=>$this->adminUserId,
                    'created_at'=>$serviceDate,'updated_at'=>now(),
                ];
            }
            DB::table('insurance_claims')->insert($rows);
            $this->info('insurance_claims', count($rows));
        }

        if (DB::table('insurance_pre_authorizations')->count() === 0) {
            $pis = DB::table('patient_insurances')->get();
            $rows = [];
            $procedures = [
                ['Root canal treatment + crown', 'K03.5', 'D3310'],
                ['Laser hair removal full face', 'L85.3', '17999'],
                ['Skin biopsy with pathology', 'D23.9', '11100'],
                ['Dental implant single tooth', 'K08.1', 'D6010'],
                ['PRP therapy for alopecia', 'L65.9', '0232T'],
                ['Chemical peel medium depth', 'L71.0', '15788'],
                ['Orthodontic consultation + plan', 'K07.0', 'D8070'],
            ];
            $statuses = ['pending','approved','approved','partially_approved','rejected'];
            for ($i = 0; $i < 7; $i++) {
                $pi = $pis->random();
                $proc = $this->pick($procedures);
                $status = $this->pick($statuses);
                $est = random_int(800, 8000);
                $rows[] = [
                    'auth_number'=>'AUTH-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'patient_insurance_id'=>$pi->id,
                    'patient_id'=>$pi->patient_id,
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'procedure_description'=>$proc[0],
                    'icd_code'=>$proc[1],'cpt_code'=>$proc[2],
                    'estimated_cost'=>$est,
                    'status'=>$status,
                    'approved_amount'=>$status==='approved' ? $est : ($status==='partially_approved' ? (int)($est*0.6) : null),
                    'valid_from'=>now()->toDateString(),
                    'valid_until'=>now()->addMonths(3)->toDateString(),
                    'conditions'=>$status==='partially_approved' ? 'Coverage limited to 60% of estimated cost' : null,
                    'rejection_reason'=>$status==='rejected' ? 'Cosmetic procedure not covered' : null,
                    'requested_by'=>$this->adminUserId,
                    'created_at'=>now()->subDays(random_int(1,30)),'updated_at'=>now(),
                ];
            }
            DB::table('insurance_pre_authorizations')->insert($rows);
            $this->info('insurance_pre_authorizations', count($rows));
        }
    }

    // ── Cosmetic ─────────────────────────────────────────────────────
    protected function seedCosmetic(): void
    {
        if (DB::table('cosmetic_procedures')->count() === 0) {
            $items = [
                ['Botox', 'بوتوكس', 'injectable', 1500, 20, 1],
                ['Dermal Fillers', 'فيلر', 'injectable', 2500, 30, 2],
                ['Lip Fillers', 'فيلر الشفاه', 'injectable', 2200, 30, 1],
                ['HIFU Face Lift', 'هايفو شد الوجه', 'laser', 3000, 60, 0],
                ['Laser Hair Removal — Full Body', 'ليزر إزالة شعر كامل الجسم', 'laser', 1800, 90, 0],
                ['Carbon Laser Peel', 'تقشير كربوني', 'laser', 900, 45, 1],
                ['Hydrafacial', 'هيدرافيشل', 'mechanical', 700, 60, 0],
                ['Mesotherapy for Hair', 'ميزوثيرابي للشعر', 'injectable', 1200, 30, 0],
                ['PRP — Platelet Rich Plasma', 'بلازما (PRP)', 'injectable', 1500, 45, 1],
                ['Chemical Peel', 'تقشير كيميائي', 'chemical', 800, 45, 3],
                ['Microneedling', 'ميكرونيدلينج', 'mechanical', 1100, 60, 2],
                ['Cryotherapy', 'كرايوثيرابي', 'other', 600, 30, 1],
                ['Threading Lift', 'خيوط الشد', 'thread', 4500, 90, 5],
                ['CoolSculpting', 'كول سكالبتينج', 'other', 3500, 90, 1],
                ['Fractional CO2 Laser', 'ليزر فراكشنال CO2', 'laser', 2800, 60, 7],
            ];
            $rows = [];
            foreach ($items as $i => $it) {
                $rows[] = [
                    'name_en'=>$it[0],'name_ar'=>$it[1],'category'=>$it[2],
                    'description'=>"Professional {$it[0]} procedure by certified specialists.",
                    'default_price'=>$it[3],'default_duration_minutes'=>$it[4],'recovery_days'=>$it[5],
                    'is_active'=>1,'display_order'=>$i,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('cosmetic_procedures')->insert($rows);
            $this->info('cosmetic_procedures', count($rows));
        }

        if (DB::table('cosmetic_packages')->count() === 0) {
            $procs = DB::table('cosmetic_procedures')->get();
            $packages = [
                ['Hair Removal 6-Session Package','إزالة شعر 6 جلسات',6],
                ['Carbon Laser 4-Session Package','تقشير كربوني 4 جلسات',4],
                ['Hydrafacial Monthly Plan','هيدرافيشل شهري',3],
                ['PRP Hair Restoration 5-Session','بلازما الشعر 5 جلسات',5],
                ['Mesotherapy 8-Session','ميزوثيرابي 8 جلسات',8],
                ['Microneedling 3-Session','ميكرونيدلينج 3 جلسات',3],
                ['Chemical Peel Series','تقشير كيميائي 4 جلسات',4],
                ['Anti-Aging Bundle','باقة مكافحة الشيخوخة',5],
            ];
            $rows = [];
            foreach ($packages as $p) {
                $proc = $procs->random();
                $rows[] = [
                    'name_en'=>$p[0],'name_ar'=>$p[1],
                    'procedure_id'=>$proc->id,
                    'total_sessions'=>$p[2],
                    'total_price'=>round($proc->default_price * $p[2] * 0.8, 2),
                    'validity_days'=>365,
                    'description'=>"Discounted bundle — save ~20% vs single sessions.",
                    'is_active'=>1,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('cosmetic_packages')->insert($rows);
            $this->info('cosmetic_packages', count($rows));
        }

        if (DB::table('cosmetic_sessions')->count() === 0) {
            $procs = DB::table('cosmetic_procedures')->get();
            $pkgs = DB::table('cosmetic_packages')->get();
            $adultIds = array_diff($this->patientIds, $this->childPatientIds);
            $adultIds = array_values($adultIds);
            $rows = [];
            for ($i = 0; $i < 35; $i++) {
                $proc = $procs->random();
                $date = Carbon::now()->subDays(random_int(0, 90));
                $completed = random_int(0, 100) < 75;
                $rows[] = [
                    'patient_id'=>$this->pick($adultIds),
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'package_id'=>random_int(0,1) ? $pkgs->random()->id : null,
                    'procedure_id'=>$proc->id,
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'session_number'=>random_int(1,6),
                    'area_treated'=>$this->pick(['Full face','Upper lip','Cheeks','Forehead','Chin','Neck','Underarms','Legs','Scalp']),
                    'product_used'=>$this->pick(['Allergan Botox 100u','Juvederm Voluma','Restylane Kysse','Radiesse',null]),
                    'dose_units'=>random_int(0,1) ? random_int(10,50) : null,
                    'cost'=>$proc->default_price,
                    'before_photo_path'=>null,'after_photo_path'=>null,
                    'completed_at'=>$completed ? $date->copy()->addHour() : null,
                    'notes'=>$completed ? 'Session completed without complications.' : null,
                    'created_at'=>$date,'updated_at'=>$date,
                ];
            }
            DB::table('cosmetic_sessions')->insert($rows);
            $this->info('cosmetic_sessions', count($rows));
        }

        if (DB::table('cosmetic_photos')->count() === 0) {
            $sessions = DB::table('cosmetic_sessions')->whereNotNull('completed_at')->get();
            $rows = [];
            foreach ($sessions->take(20) as $s) {
                $date = Carbon::parse($s->completed_at)->toDateString();
                $rows[] = [
                    'patient_id'=>$s->patient_id,'session_id'=>$s->id,'procedure_id'=>$s->procedure_id,
                    'category'=>'before','body_area'=>$s->area_treated,
                    'taken_at'=>$date,'image_path'=>'demo/cosmetic/before_'.$s->id.'.jpg',
                    'notes'=>null,'created_at'=>now(),'updated_at'=>now(),
                ];
                $rows[] = [
                    'patient_id'=>$s->patient_id,'session_id'=>$s->id,'procedure_id'=>$s->procedure_id,
                    'category'=>'after','body_area'=>$s->area_treated,
                    'taken_at'=>Carbon::parse($date)->addDays(14)->toDateString(),
                    'image_path'=>'demo/cosmetic/after_'.$s->id.'.jpg',
                    'notes'=>null,'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('cosmetic_photos')->insert($rows);
            $this->info('cosmetic_photos', count($rows));
        }

        if (DB::table('cosmetic_consents')->count() === 0) {
            $sessions = DB::table('cosmetic_sessions')->get();
            $rows = [];
            foreach ($sessions->take(20) as $s) {
                $rows[] = [
                    'patient_id'=>$s->patient_id,'procedure_id'=>$s->procedure_id,'session_id'=>$s->id,
                    'consent_text'=>'I acknowledge the risks and benefits of the cosmetic procedure as explained to me.',
                    'signed_at'=>$s->created_at,
                    'signature_path'=>'demo/signatures/consent_'.$s->id.'.png',
                    'witnessed_by'=>'Dr. '.$this->firstName().' '.$this->lastName(),
                    'created_at'=>$s->created_at,'updated_at'=>$s->created_at,
                ];
            }
            if ($rows) DB::table('cosmetic_consents')->insert($rows);
            $this->info('cosmetic_consents', count($rows));
        }
    }

    // ── Dermatology ──────────────────────────────────────────────────
    protected function seedDermatology(): void
    {
        if (DB::table('skin_conditions')->count() === 0) {
            $conds = [
                ['acne','Acne vulgaris','حب الشباب','moderate'],
                ['acne','Cystic acne','حب الشباب الكيسي','severe'],
                ['psoriasis','Plaque psoriasis','الصدفية','moderate'],
                ['eczema','Atopic dermatitis','إكزيما','mild'],
                ['eczema','Contact dermatitis','التهاب الجلد التماسي','mild'],
                ['melasma','Melasma','الكلف','moderate'],
                ['vitiligo','Vitiligo','البهاق','mild'],
                ['rosacea','Rosacea','الوردية','mild'],
                ['dermatitis','Seborrheic dermatitis','التهاب الجلد الدهني','moderate'],
                ['fungal','Tinea corporis','سعفة الجسم','mild'],
            ];
            $areas = ['Face','Back','Chest','Arms','Legs','Scalp','Hands','Neck'];
            $rows = [];
            for ($i = 0; $i < 18; $i++) {
                $c = $this->pick($conds);
                $rows[] = [
                    'patient_id'=>$this->pick($this->patientIds),
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'name_ar'=>$c[2],'name_en'=>$c[1],
                    'category'=>$c[0],'severity'=>$c[3],
                    'body_area'=>$this->pick($areas),
                    'diagnosed_at'=>Carbon::now()->subDays(random_int(10,180))->toDateString(),
                    'status'=>$this->pick(['active','active','monitoring','resolved']),
                    'notes'=>$this->sentence(),
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('skin_conditions')->insert($rows);
            $this->info('skin_conditions', count($rows));
        }

        if (DB::table('derma_sessions')->count() === 0) {
            $rows = [];
            $types = ['laser','peel','phototherapy','injection','cryotherapy'];
            for ($i = 0; $i < 25; $i++) {
                $d = Carbon::now()->subDays(random_int(0,90));
                $total = random_int(3,8);
                $num = random_int(1,$total);
                $rows[] = [
                    'patient_id'=>$this->pick($this->patientIds),
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'session_type'=>$this->pick($types),
                    'area_treated'=>$this->pick(['Face','Back','Scalp','Arms','Full body']),
                    'product_used'=>$this->pick(['Salicylic acid 20%','TCA 30%','UVB narrowband','Liquid nitrogen','Kenalog injection']),
                    'settings_json'=>json_encode(['fluence'=>random_int(5,30),'spot_size'=>'10mm']),
                    'session_number'=>$num,'total_sessions'=>$total,
                    'cost'=>random_int(400,1500),
                    'completed_at'=>$d->copy()->setTime(random_int(9,17), 0),
                    'next_session_date'=>$num < $total ? $d->copy()->addDays(21)->toDateString() : null,
                    'notes'=>'Patient tolerated procedure well.',
                    'created_at'=>$d,'updated_at'=>$d,
                ];
            }
            DB::table('derma_sessions')->insert($rows);
            $this->info('derma_sessions', count($rows));
        }

        if (DB::table('derma_photos')->count() === 0) {
            $sessions = DB::table('derma_sessions')->get();
            $rows = [];
            foreach ($sessions->take(18) as $s) {
                $rows[] = [
                    'patient_id'=>$s->patient_id,'visit_id'=>$s->visit_id,'session_id'=>$s->id,
                    'category'=>$this->pick(['before','after','progress']),
                    'body_area'=>$s->area_treated,
                    'taken_at'=>Carbon::parse($s->created_at)->toDateString(),
                    'image_path'=>'demo/derma/photo_'.$s->id.'.jpg',
                    'notes'=>null,'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('derma_photos')->insert($rows);
            $this->info('derma_photos', count($rows));
        }

        if (DB::table('derma_prescription_templates')->count() === 0) {
            $tpls = [
                ['acne','حب الشباب','Acne regimen','روتين علاج حب الشباب'],
                ['eczema','إكزيما','Eczema maintenance','صيانة الإكزيما'],
                ['psoriasis','صدفية','Psoriasis topical therapy','علاج الصدفية الموضعي'],
                ['melasma','كلف','Melasma brightening','تفتيح الكلف'],
                ['rosacea','وردية','Rosacea control','التحكم بالوردية'],
                ['fungal','فطريات','Antifungal regimen','علاج فطري'],
                ['dermatitis','التهاب جلدي','Dermatitis calm','تهدئة التهاب الجلد'],
                ['vitiligo','بهاق','Vitiligo topical protocol','بروتوكول البهاق الموضعي'],
            ];
            $rows = [];
            foreach ($tpls as $i => $t) {
                $rows[] = [
                    'name_ar'=>$t[3],'name_en'=>$t[2],
                    'condition_category'=>$t[0],
                    'diagnosis_ar'=>$t[1],'diagnosis_en'=>$t[0],
                    'items'=>json_encode([
                        ['medication'=>'Tretinoin 0.025% cream','dosage'=>'pea-sized','frequency'=>'at night','duration'=>'12 weeks'],
                        ['medication'=>'Clindamycin 1% gel','dosage'=>'thin layer','frequency'=>'twice daily','duration'=>'8 weeks'],
                    ]),
                    'notes_ar'=>'يُستخدم حسب الحاجة','notes_en'=>'Apply sunscreen during daytime.',
                    'is_active'=>1,'sort_order'=>$i,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('derma_prescription_templates')->insert($rows);
            $this->info('derma_prescription_templates', count($rows));
        }
    }

    // ── Dental extras ────────────────────────────────────────────────
    protected function seedDentalExtras(): void
    {
        if (DB::table('dental_chart_entries')->count() === 0) {
            $types = ['caries','restoration','crown','extraction','endodontic','missing','implant'];
            $conditions = ['healthy','caries','filled','crown','missing','decayed','root_canal'];
            $rows = [];
            for ($i = 0; $i < 40; $i++) {
                $date = Carbon::now()->subDays(random_int(0,120));
                $type = $this->pick($types);
                $rows[] = [
                    'patient_id'=>$this->pick($this->patientIds),
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'tooth_number'=>random_int(11, 48),
                    'entry_type'=>$type,
                    'title'=>ucfirst($type).' on tooth',
                    'description'=>'Clinical finding — '.$type,
                    'condition_before'=>$this->pick($conditions),
                    'condition_after'=>$this->pick($conditions),
                    'surfaces'=>json_encode($this->maybe(['M','D','O','L','B'], random_int(1,3))),
                    'cost'=>random_int(100, 2000),
                    'status'=>$this->pick(['recorded','planned','in_progress','completed']),
                    'media'=>null,'entry_date'=>$date->toDateString(),
                    'created_at'=>$date,'updated_at'=>$date,
                ];
            }
            DB::table('dental_chart_entries')->insert($rows);
            $this->info('dental_chart_entries', count($rows));
        }

        if (DB::table('dental_comparisons')->count() === 0) {
            $categories = ['orthodontic','cosmetic','implant','whitening','restoration'];
            $rows = [];
            for ($i = 0; $i < 10; $i++) {
                $pid = $this->pick($this->patientIds);
                $beforeDate = Carbon::now()->subMonths(random_int(3,12));
                $afterDate = $beforeDate->copy()->addMonths(random_int(2,6));
                $cat = $this->pick($categories);
                $rows[] = [
                    'patient_id'=>$pid,
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'treatment_plan_id'=>null,
                    'title_en'=>ucfirst($cat).' transformation',
                    'title_ar'=>'تحول '.$cat,
                    'description'=>'Before/after comparison of treatment outcome.',
                    'category'=>$cat,
                    'before_image_path'=>'demo/dental/before_'.$i.'.jpg',
                    'before_date'=>$beforeDate->toDateString(),
                    'before_notes'=>'Initial presentation',
                    'after_image_path'=>'demo/dental/after_'.$i.'.jpg',
                    'after_date'=>$afterDate->toDateString(),
                    'after_notes'=>'Post-treatment result',
                    'tooth_numbers'=>implode(',', [random_int(11,48), random_int(11,48)]),
                    'is_visible_to_patient'=>1,
                    'is_featured'=>(int)(random_int(0,1)),
                    'sort_order'=>$i,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('dental_comparisons')->insert($rows);
            $this->info('dental_comparisons', count($rows));
        }

        if (DB::table('dental_lab_orders')->count() === 0) {
            $labs = ['Smile Dental Lab','Crown & Bridge Lab','Perfect Fit Laboratory','ProDental Lab'];
            $items = ['crown','bridge','veneer','denture','night_guard','implant_crown'];
            $statuses = ['ordered','sent','in_production','received','fitted','cancelled'];
            $shades = ['A1','A2','A3','B1','B2','C1'];
            $materials = ['Zirconia','E-max','PFM','Emax Press','Full metal'];
            $rows = [];
            for ($i = 0; $i < 18; $i++) {
                $order = Carbon::now()->subDays(random_int(0,60));
                $status = $this->pick($statuses);
                $rows[] = [
                    'patient_id'=>$this->pick($this->patientIds),
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'dental_treatment_id'=>null,'invoice_item_id'=>null,
                    'lab_name'=>$this->pick($labs),
                    'order_number'=>'LAB-'.date('Y').'-'.str_pad((string)($i+1),4,'0',STR_PAD_LEFT),
                    'item_type'=>$this->pick($items),
                    'tooth_number'=>(string)random_int(11,48),
                    'shade'=>$this->pick($shades),
                    'material'=>$this->pick($materials),
                    'cost'=>random_int(400, 2500),
                    'patient_charge'=>random_int(800, 4000),
                    'status'=>$status,
                    'order_date'=>$order->toDateString(),
                    'expected_date'=>$order->copy()->addDays(10)->toDateString(),
                    'delivered_date'=>in_array($status,['received','fitted']) ? $order->copy()->addDays(8)->toDateString() : null,
                    'notes'=>null,'special_instructions'=>'Standard fit check required.',
                    'created_at'=>$order,'updated_at'=>now(),
                ];
            }
            DB::table('dental_lab_orders')->insert($rows);
            $this->info('dental_lab_orders', count($rows));
        }

        if (DB::table('dental_prescription_templates')->count() === 0) {
            $tpls = [
                ['Post-Extraction Pain','علاج الألم بعد الخلع','extraction'],
                ['Post-Root Canal','علاج بعد عصب','endodontic'],
                ['Acute Dental Abscess','خراج الأسنان الحاد','infection'],
                ['Perio Maintenance','صيانة اللثة','periodontic'],
                ['Pre-Surgical Prophylaxis','وقاية قبل الجراحة','surgical'],
                ['Dry Socket Management','علاج السنخ الجاف','extraction'],
                ['Pediatric Dental Pain','ألم أسنان الأطفال','pediatric'],
                ['Orthodontic Pain','ألم تقويم الأسنان','ortho'],
            ];
            $itemRows = [];
            $tplInsert = [];
            foreach ($tpls as $i => $t) {
                $tplInsert[] = [
                    'name_ar'=>$t[1],'name_en'=>$t[0],
                    'treatment_type'=>$t[2],
                    'diagnosis_ar'=>$t[1],'diagnosis_en'=>$t[0],
                    'notes_ar'=>'راقب الأعراض','notes_en'=>'Monitor symptoms; return if worsens.',
                    'auto_apply'=>0,'is_active'=>1,'sort_order'=>$i,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('dental_prescription_templates')->insert($tplInsert);

            $meds = [
                ['Amoxicillin 500mg','1 cap','TID','7 days'],
                ['Ibuprofen 400mg','1 tab','Q6H PRN','5 days'],
                ['Paracetamol 500mg','1-2 tabs','Q6H','as needed'],
                ['Chlorhexidine 0.12% rinse','10ml rinse','BID','14 days'],
                ['Metronidazole 500mg','1 tab','TID','5 days'],
            ];
            foreach (DB::table('dental_prescription_templates')->get() as $tpl) {
                foreach (array_slice($meds, 0, random_int(2,3)) as $ord => $m) {
                    $itemRows[] = [
                        'template_id'=>$tpl->id,
                        'medication_name'=>$m[0],'dosage'=>$m[1],'frequency'=>$m[2],'duration'=>$m[3],
                        'instructions_ar'=>'بعد الأكل','instructions_en'=>'Take after meals',
                        'sort_order'=>$ord,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            if ($itemRows) DB::table('dental_prescription_template_items')->insert($itemRows);
            $this->info('dental_prescription_templates', count($tplInsert));
            $this->info('dental_prescription_template_items', count($itemRows));
        }

        // dental_scheduled_followups requires a dental_treatment_id
        if (DB::table('dental_scheduled_followups')->count() === 0) {
            $treatments = DB::table('dental_treatments')->limit(20)->get();
            $rows = [];
            foreach ($treatments as $i => $t) {
                $scheduled = Carbon::now()->addDays(random_int(3, 60));
                $rows[] = [
                    'dental_treatment_id'=>$t->id,
                    'patient_id'=>$t->patient_id,
                    'doctor_id'=>$t->doctor_id ?? $this->pick($this->doctorIds),
                    'followup_rule_id'=>null,'booking_id'=>null,
                    'scheduled_date'=>$scheduled->toDateString(),
                    'status'=>$this->pick(['pending','pending','sent','completed']),
                    'booking_created_at'=>null,
                    'sms_sent_at'=>random_int(0,1) ? now()->subDays(random_int(1,5)) : null,
                    'reminder_sent_at'=>null,
                    'notes'=>'Routine dental follow-up',
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('dental_scheduled_followups')->insert($rows);
            $this->info('dental_scheduled_followups', count($rows));
        }

        if (DB::table('dental_smart_notifications')->count() === 0) {
            $rows = [];
            for ($i = 0; $i < 12; $i++) {
                $pid = $this->pick($this->patientIds);
                $phone = DB::table('patients')->where('id',$pid)->value('phone');
                $rows[] = [
                    'patient_id'=>$pid,
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'type'=>$this->pick(['post_treatment','followup','recall','checkup_reminder']),
                    'channel'=>$this->pick(['sms','whatsapp']),
                    'notifiable_type'=>null,'notifiable_id'=>null,
                    'message_ar'=>'نتمنى لكم سلامة دائمة. يرجى الحضور للمتابعة.',
                    'message_en'=>'We hope you are doing well. Please schedule your follow-up.',
                    'status'=>$this->pick(['pending','sent','sent','failed']),
                    'scheduled_at'=>now()->addDays(random_int(0,7)),
                    'sent_at'=>random_int(0,1) ? now()->subDays(random_int(1,5)) : null,
                    'failure_reason'=>null,
                    'phone'=>$phone,
                    'sms_provider'=>'twilio',
                    'delay_hours'=>random_int(0,48),
                    'is_auto'=>1,
                    'patient_responded'=>(int)(random_int(0,1)),
                    'dedup_key'=>'demo-notif-'.$i.'-'.uniqid(),
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('dental_smart_notifications')->insert($rows);
            $this->info('dental_smart_notifications', count($rows));
        }
    }

    // ── Pediatric ────────────────────────────────────────────────────
    protected function seedPediatric(): void
    {
        if (empty($this->childPatientIds)) {
            $this->command->warn('    no child patients found — skipping pediatric');
            return;
        }

        if (DB::table('pediatric_milestones')->count() === 0) {
            $mils = [
                ['gross_motor','sits_unsupported','Sits without support','يجلس بدون مساعدة',6],
                ['gross_motor','walks_alone','Walks alone','يمشي لوحده',12],
                ['gross_motor','runs','Runs steadily','يركض بثبات',24],
                ['fine_motor','pincer_grasp','Pincer grasp','الإمساك الكمّاشي',9],
                ['fine_motor','tower_3_blocks','Builds tower of 3 blocks','يبني برجاً من 3 مكعبات',18],
                ['language','first_words','Says first words','ينطق كلماته الأولى',12],
                ['language','two_word_phrases','Uses two-word phrases','يستخدم عبارات من كلمتين',24],
                ['social','social_smile','Social smile','ابتسامة اجتماعية',2],
                ['social','stranger_anxiety','Shows stranger anxiety','قلق الغرباء',9],
            ];
            $rows = [];
            foreach ($this->childPatientIds as $pid) {
                foreach ($mils as $m) {
                    $dob = DB::table('patients')->where('id',$pid)->value('date_of_birth');
                    $ageM = $dob ? Carbon::parse($dob)->diffInMonths(now()) : 18;
                    $achieved = $ageM >= $m[4];
                    $rows[] = [
                        'patient_id'=>$pid,
                        'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                        'doctor_id'=>$this->pick($this->doctorIds),
                        'assessment_date'=>now()->subDays(random_int(1,60))->toDateString(),
                        'age_months'=>$ageM,
                        'category'=>$m[0],'milestone_key'=>$m[1],
                        'milestone_name_en'=>$m[2],'milestone_name_ar'=>$m[3],
                        'expected_age'=>$m[4].'m',
                        'status'=>$achieved ? 'achieved' : ($ageM >= $m[4]-2 ? 'emerging' : 'not_achieved'),
                        'achieved_date'=>$achieved ? Carbon::parse($dob)->addMonths($m[4])->toDateString() : null,
                        'notes'=>null,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            DB::table('pediatric_milestones')->insert($rows);
            $this->info('pediatric_milestones', count($rows));
        }

        if (DB::table('pediatric_well_child_visits')->count() === 0) {
            $schedule = [['2m',2],['4m',4],['6m',6],['9m',9],['12m',12],['18m',18],['24m',24],['3y',36],['4y',48]];
            $rows = [];
            foreach ($this->childPatientIds as $pid) {
                $dob = DB::table('patients')->where('id',$pid)->value('date_of_birth');
                $ageM = $dob ? Carbon::parse($dob)->diffInMonths(now()) : 24;
                foreach ($schedule as $s) {
                    if ($s[1] > $ageM + 2) break;
                    $visitDate = Carbon::parse($dob)->addMonths($s[1]);
                    $completed = $visitDate->isPast();
                    $rows[] = [
                        'patient_id'=>$pid,
                        'doctor_id'=>$this->pick($this->doctorIds),
                        'visit_id'=>null,
                        'schedule_key'=>$s[0],
                        'scheduled_age_months'=>$s[1],
                        'visit_date'=>$completed ? $visitDate->toDateString() : null,
                        'status'=>$completed ? 'completed' : 'scheduled',
                        'weight_kg'=>round(3 + $s[1] * 0.25 + random_int(-10,10)/10, 2),
                        'height_cm'=>round(50 + $s[1] * 1.2 + random_int(-20,20)/10, 1),
                        'head_circumference_cm'=>round(35 + $s[1] * 0.3, 1),
                        'physical_exam_notes'=>'Normal physical examination. Age-appropriate development.',
                        'development_notes'=>'Milestones on track.',
                        'feeding_notes'=>$s[1] < 6 ? 'Exclusively breastfed' : 'Transitioning to solids',
                        'safety_guidance'=>'Car seat use, childproof home, safe sleep.',
                        'vaccinations_given'=>json_encode(['DTP','Polio']),
                        'screening_tests_done'=>json_encode([]),
                        'referrals'=>json_encode([]),
                        'next_visit_date'=>$visitDate->copy()->addMonths(2)->toDateString(),
                        'notes'=>null,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            if ($rows) DB::table('pediatric_well_child_visits')->insert($rows);
            $this->info('pediatric_well_child_visits', count($rows));
        }

        if (DB::table('pediatric_chronic_conditions')->count() === 0) {
            $conds = [
                ['asthma','Asthma','الربو','mild'],
                ['congenital_heart','Ventricular septal defect','عيب الحاجز البطيني','moderate'],
                ['anemia','Iron deficiency anemia','فقر الدم بعوز الحديد','mild'],
                ['epilepsy','Febrile seizures','تشنجات حرارية','mild'],
            ];
            $rows = [];
            foreach (array_slice($this->childPatientIds, 0, 4) as $pid) {
                $c = $this->pick($conds);
                $rows[] = [
                    'patient_id'=>$pid,
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'condition_type'=>$c[0],
                    'condition_name'=>$c[1],
                    'condition_name_ar'=>$c[2],
                    'diagnosed_date'=>now()->subMonths(random_int(3,24))->toDateString(),
                    'severity'=>$c[3],
                    'current_medications'=>json_encode(['Albuterol inhaler PRN']),
                    'treatment_plan'=>'Regular follow-up every 3 months, rescue inhaler for acute episodes.',
                    'action_plan'=>'Green zone: normal; Yellow: increase albuterol; Red: ER visit.',
                    'is_active'=>1,
                    'notes'=>null,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('pediatric_chronic_conditions')->insert($rows);
            $this->info('pediatric_chronic_conditions', count($rows));
        }

        if (DB::table('pediatric_family_history')->count() === 0) {
            $conditions = [
                ['Asthma','الربو'],['Diabetes Type 2','السكري النوع الثاني'],
                ['Hypertension','ارتفاع ضغط الدم'],['Allergic rhinitis','حساسية الأنف'],
                ['Thyroid disease','أمراض الغدة الدرقية'],
            ];
            $rows = [];
            foreach ($this->childPatientIds as $pid) {
                $n = random_int(1,2);
                $selected = array_rand($conditions, $n);
                if (!is_array($selected)) $selected = [$selected];
                foreach ($selected as $idx) {
                    $c = $conditions[$idx];
                    $rows[] = [
                        'patient_id'=>$pid,
                        'doctor_id'=>$this->pick($this->doctorIds),
                        'condition'=>$c[0],'condition_ar'=>$c[1],
                        'affected_members'=>json_encode([$this->pick(['mother','father','sibling','grandparent'])]),
                        'details'=>'Documented at initial visit.',
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            if ($rows) DB::table('pediatric_family_history')->insert($rows);
            $this->info('pediatric_family_history', count($rows));
        }

        if (DB::table('pediatric_nutrition_records')->count() === 0) {
            $rows = [];
            foreach ($this->childPatientIds as $pid) {
                for ($k = 0; $k < 2; $k++) {
                    $dob = DB::table('patients')->where('id',$pid)->value('date_of_birth');
                    $ageM = $dob ? max(1, Carbon::parse($dob)->diffInMonths(now()) - $k * 6) : 12;
                    $rows[] = [
                        'patient_id'=>$pid,
                        'doctor_id'=>$this->pick($this->doctorIds),
                        'visit_id'=>null,
                        'age_months'=>$ageM,
                        'feeding_type'=>$ageM < 6 ? $this->pick(['breastfed','formula','mixed']) : null,
                        'feeds_per_day'=>$ageM < 12 ? random_int(4,8) : null,
                        'feed_duration_min'=>$ageM < 6 ? random_int(15,30) : null,
                        'formula_brand'=>null,'formula_ml_per_feed'=>null,
                        'complementary_start_age'=>$ageM > 6 ? 6 : null,
                        'introduced_foods'=>json_encode(['rice cereal','pureed vegetables','fruits']),
                        'meals_per_day'=>$ageM > 12 ? random_int(3,5) : null,
                        'diet_quality'=>$ageM > 12 ? $this->pick(['varied','limited']) : null,
                        'milk_intake'=>'daily',
                        'fruits_vegetables'=>$this->pick(['daily','sometimes']),
                        'fast_food'=>$this->pick(['rarely','weekly','never']),
                        'supplements'=>json_encode(['Vitamin D drops']),
                        'food_allergies'=>json_encode([]),
                        'eating_problems'=>json_encode([]),
                        'notes'=>'Nutrition status appropriate for age.',
                        'created_at'=>now()->subDays($k*30),'updated_at'=>now(),
                    ];
                }
            }
            if ($rows) DB::table('pediatric_nutrition_records')->insert($rows);
            $this->info('pediatric_nutrition_records', count($rows));
        }

        if (DB::table('pediatric_screening_tests')->count() === 0) {
            $tests = ['mchat','vision','hearing','vanderbilt_parent'];
            $results = ['normal','low_risk','medium_risk','negative'];
            $rows = [];
            foreach ($this->childPatientIds as $pid) {
                for ($k = 0; $k < 2; $k++) {
                    $dob = DB::table('patients')->where('id',$pid)->value('date_of_birth');
                    $ageM = $dob ? Carbon::parse($dob)->diffInMonths(now()) : 24;
                    $rows[] = [
                        'patient_id'=>$pid,
                        'visit_id'=>null,
                        'doctor_id'=>$this->pick($this->doctorIds),
                        'test_date'=>now()->subDays(random_int(10,120))->toDateString(),
                        'age_months'=>$ageM,
                        'test_type'=>$this->pick($tests),
                        'answers'=>json_encode(['q1'=>'yes','q2'=>'no','q3'=>'yes']),
                        'total_score'=>random_int(0, 20),
                        'result'=>$this->pick($results),
                        'interpretation'=>'Screening results within acceptable range.',
                        'recommendations'=>'Continue routine surveillance.',
                        'notes'=>null,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            if ($rows) DB::table('pediatric_screening_tests')->insert($rows);
            $this->info('pediatric_screening_tests', count($rows));
        }
    }

    // ── HR ───────────────────────────────────────────────────────────
    protected function seedHr(): void
    {
        if (DB::table('departments')->count() === 0) {
            $depts = [
                ['Reception','الاستقبال'],['Nursing','التمريض'],['Laboratory','المختبر'],
                ['Pharmacy','الصيدلية'],['IT','تقنية المعلومات'],['Finance','المالية'],
                ['Human Resources','الموارد البشرية'],
            ];
            $rows = [];
            foreach ($depts as $i => $d) {
                $rows[] = [
                    'name_en'=>$d[0],'name_ar'=>$d[1],
                    'description'=>"Responsible for {$d[0]} operations.",
                    'manager_id'=>null,'is_active'=>1,'display_order'=>$i,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('departments')->insert($rows);
            $this->info('departments', count($rows));
        }

        // Create staff users for employees that aren't already staff
        if (DB::table('employees')->count() === 0) {
            $depts = DB::table('departments')->get();
            $roleId = DB::table('roles')->where('name','secretary')->value('id') ?? 6;
            $userRows = [];
            $baseEmail = 'staff';
            $names = [
                ['Mona Al-Otaibi','منى العتيبي','Receptionist'],
                ['Fahad Al-Ghamdi','فهد الغامدي','Head Nurse'],
                ['Lina Al-Shehri','لينا الشهري','Staff Nurse'],
                ['Yousef Al-Harbi','يوسف الحربي','Lab Technician'],
                ['Aisha Al-Dossari','عائشة الدوسري','Lab Technician'],
                ['Majed Al-Qahtani','ماجد القحطاني','Pharmacist'],
                ['Reem Al-Mutairi','ريم المطيري','IT Specialist'],
                ['Bandar Al-Zahrani','بندر الزهراني','Accountant'],
                ['Huda Al-Subaie','هدى السبيعي','HR Officer'],
                ['Saleh Al-Anazi','صالح العنزي','Receptionist'],
                ['Nouf Al-Malki','نوف المالكي','Staff Nurse'],
                ['Talal Al-Harthy','طلال الحارثي','Finance Manager'],
                ['Rania Al-Shammari','رانيا الشمري','HR Manager'],
                ['Khaled Al-Juhani','خالد الجهني','IT Manager'],
                ['Sara Al-Abdullah','سارة العبدالله','Head Pharmacist'],
            ];
            $createdUserIds = [];
            foreach ($names as $i => $n) {
                $email = "staff{$i}@aura.com";
                $uid = DB::table('users')->where('email',$email)->value('id');
                if (!$uid) {
                    $uid = DB::table('users')->insertGetId([
                        'name'=>$n[0],'email'=>$email,'password'=>Hash::make('password'),
                        'role_id'=>$roleId,'is_active'=>1,
                        'created_at'=>now(),'updated_at'=>now(),
                    ]);
                }
                $createdUserIds[] = ['uid'=>$uid,'name'=>$n];
            }

            $empRows = [];
            foreach ($createdUserIds as $i => $u) {
                $dept = $depts[$i % count($depts)];
                $hireDate = Carbon::now()->subMonths(random_int(6, 60));
                $empRows[] = [
                    'user_id'=>$u['uid'],
                    'employee_number'=>'EMP-'.str_pad((string)($i+1),4,'0',STR_PAD_LEFT),
                    'department_id'=>$dept->id,
                    'job_title_en'=>$u['name'][2],
                    'job_title_ar'=>$u['name'][2],
                    'hire_date'=>$hireDate->toDateString(),
                    'contract_type'=>$this->pick(['full_time','full_time','full_time','part_time']),
                    'contract_end_date'=>null,
                    'basic_salary'=>random_int(4000, 15000),
                    'housing_allowance'=>random_int(800, 2500),
                    'transport_allowance'=>random_int(400, 1000),
                    'other_allowances'=>random_int(0, 500),
                    'national_id'=>(string)random_int(1000000000, 1999999999),
                    'phone'=>'+9665'.random_int(10000000, 99999999),
                    'emergency_contact_name'=>$this->fullName(),
                    'emergency_contact_phone'=>'+9665'.random_int(10000000, 99999999),
                    'address'=>$this->addressAr(),
                    'bank_name'=>$this->pick(['Al Rajhi Bank','SABB','NCB','Riyad Bank']),
                    'bank_account_number'=>'SA'.random_int(10,99).str_repeat('0', 10).random_int(10000000, 99999999),
                    'insurance_number'=>'INS-'.random_int(100000, 999999),
                    'status'=>'active',
                    'termination_date'=>null,'termination_reason'=>null,
                    'created_at'=>$hireDate,'updated_at'=>now(),
                ];
            }
            DB::table('employees')->insert($empRows);
            $this->info('employees', count($empRows));
        }

        // employee_shifts — needs at least one shift
        if (DB::table('employee_shifts')->count() === 0) {
            $shiftId = DB::table('shifts')->value('id');
            if (!$shiftId) {
                $shiftId = DB::table('shifts')->insertGetId([
                    'name_en'=>'Morning Shift','name_ar'=>'وردية صباحية',
                    'start_time'=>'08:00:00','end_time'=>'16:00:00','is_active'=>1,
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
            $rows = [];
            $empUserIds = DB::table('employees')->pluck('user_id')->all();
            foreach ($empUserIds as $uid) {
                for ($day = 0; $day < 5; $day++) {
                    $rows[] = [
                        'user_id'=>$uid,'shift_id'=>$shiftId,'day_of_week'=>$day,
                        'effective_from'=>now()->subMonths(3)->toDateString(),
                        'effective_to'=>null,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            if ($rows) DB::table('employee_shifts')->insert($rows);
            $this->info('employee_shifts', count($rows));
        }

        // Salary slips for current month + 3 previous months (so payroll page is never empty)
        $emps = DB::table('employees')->get();
        $now = Carbon::now();
        $rows = [];
        foreach ($emps as $emp) {
            // 0 = current month, 1, 2, 3 = previous months
            for ($m = 0; $m <= 3; $m++) {
                $d = $now->copy()->subMonths($m);
                // Skip if slip already exists for this employee/month/year
                $exists = DB::table('salary_slips')
                    ->where('employee_id', $emp->id)
                    ->where('month', $d->month)
                    ->where('year', $d->year)
                    ->exists();
                if ($exists) continue;

                $basic = (float) $emp->basic_salary;
                $housing = (float) $emp->housing_allowance;
                $transport = (float) $emp->transport_allowance;
                $other = (float) $emp->other_allowances;
                $overtime = random_int(0, 1) ? random_int(100, 800) : 0;
                $bonus = random_int(0, 1) ? random_int(200, 1500) : 0;
                $commission = random_int(0, 1) ? random_int(0, 2000) : 0;

                $insurance = round($basic * 0.11, 2);
                $tax = round($basic * 0.05, 2);
                $absence = random_int(0, 1) ? random_int(0, 300) : 0;
                $advance = random_int(0, 1) ? random_int(0, 500) : 0;
                $penalty = random_int(0, 1) ? random_int(0, 200) : 0;

                $earn = $basic + $housing + $transport + $other + $overtime + $bonus + $commission;
                $deduct = $insurance + $tax + $absence + $advance + $penalty;
                $net = $earn - $deduct;

                // Status: current month = draft/approved, previous = paid
                $status = $m === 0 ? (random_int(0, 1) ? 'draft' : 'approved') : 'paid';

                $rows[] = [
                    'slip_number' => 'SAL-' . $d->format('Ym') . '-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT),
                    'employee_id' => $emp->id,
                    'month' => $d->month, 'year' => $d->year,
                    'basic_salary' => $basic,
                    'housing_allowance' => $housing,
                    'transport_allowance' => $transport,
                    'other_allowances' => $other,
                    'overtime_amount' => $overtime, 'bonus' => $bonus, 'commission_amount' => $commission,
                    'insurance_deduction' => $insurance, 'tax_deduction' => $tax, 'absence_deduction' => $absence,
                    'advance_deduction' => $advance, 'penalty_deduction' => $penalty, 'other_deductions' => 0,
                    'total_earnings' => $earn,
                    'total_deductions' => $deduct,
                    'net_salary' => $net,
                    'status' => $status,
                    'approved_by' => $status !== 'draft' ? $this->adminUserId : null,
                    'approved_at' => $status !== 'draft' ? $d->copy()->endOfMonth() : null,
                    'paid_by' => $status === 'paid' ? $this->adminUserId : null,
                    'paid_at' => $status === 'paid' ? $d->copy()->endOfMonth()->addDays(2) : null,
                    'payment_method' => $status === 'paid' ? 'bank_transfer' : null,
                    'payment_reference' => $status === 'paid' ? 'TRF-' . random_int(100000, 999999) : null,
                    'notes' => null, 'created_by' => $this->adminUserId,
                    'created_at' => $d->copy()->endOfMonth(), 'updated_at' => now(),
                ];
            }
        }
        if (! empty($rows)) {
            DB::table('salary_slips')->insert($rows);
            $this->info('salary_slips', count($rows));
        }

        if (DB::table('advances')->count() === 0) {
            $emps = DB::table('employees')->inRandomOrder()->limit(7)->get();
            $rows = [];
            foreach ($emps as $emp) {
                $amt = random_int(2000, 10000);
                $monthly = round($amt / 3, 2);
                $rows[] = [
                    'employee_id'=>$emp->id,
                    'amount'=>$amt,
                    'monthly_installment'=>$monthly,
                    'remaining_balance'=>$amt - $monthly,
                    'total_paid'=>$monthly,
                    'reason'=>$this->pick(['Medical emergency','Home repair','Family expenses','Wedding']),
                    'status'=>$this->pick(['active','approved','completed','pending']),
                    'approved_by'=>$this->adminUserId,
                    'approved_at'=>now()->subMonths(2),
                    'start_month'=>now()->subMonths(1)->month,
                    'start_year'=>now()->subMonths(1)->year,
                    'created_by'=>$this->adminUserId,
                    'created_at'=>now()->subMonths(2),'updated_at'=>now(),
                ];
            }
            DB::table('advances')->insert($rows);
            $this->info('advances', count($rows));
        }

        if (DB::table('leaves')->count() === 0) {
            $empUserIds = DB::table('employees')->pluck('user_id')->all();
            $rows = [];
            for ($i = 0; $i < 12; $i++) {
                $start = Carbon::now()->addDays(random_int(-30, 30));
                $end = $start->copy()->addDays(random_int(1, 7));
                $rows[] = [
                    'user_id'=>$this->pick($empUserIds),
                    'leave_type'=>$this->pick(['annual','sick','personal','unpaid']),
                    'start_date'=>$start->toDateString(),
                    'end_date'=>$end->toDateString(),
                    'reason'=>$this->pick(['Family vacation','Medical leave','Personal matter','Annual leave']),
                    'status'=>$this->pick(['pending','approved','approved','rejected']),
                    'approved_by'=>$this->adminUserId,
                    'created_at'=>now()->subDays(random_int(1,30)),'updated_at'=>now(),
                ];
            }
            DB::table('leaves')->insert($rows);
            $this->info('leaves', count($rows));
        }

        if (DB::table('penalties')->count() === 0) {
            $emps = DB::table('employees')->inRandomOrder()->limit(5)->get();
            $rows = [];
            foreach ($emps as $emp) {
                $rows[] = [
                    'employee_id'=>$emp->id,
                    'type'=>$this->pick(['penalty','penalty','reward']),
                    'amount'=>random_int(100, 800),
                    'reason'=>$this->pick(['Late arrival','Outstanding performance','Policy violation','Excellent patient feedback']),
                    'date'=>now()->subDays(random_int(1,60))->toDateString(),
                    'applied_to_salary'=>(int)(random_int(0,1)),
                    'salary_slip_id'=>null,
                    'created_by'=>$this->adminUserId,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('penalties')->insert($rows);
            $this->info('penalties', count($rows));
        }

        if (DB::table('doctor_vacations')->count() === 0) {
            $rows = [];
            for ($i = 0; $i < 5; $i++) {
                $start = Carbon::now()->addDays(random_int(7, 90));
                $end = $start->copy()->addDays(random_int(3, 14));
                $rows[] = [
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'start_date'=>$start->toDateString(),
                    'end_date'=>$end->toDateString(),
                    'reason'=>$this->pick(['Annual leave','Conference','Medical','Personal']),
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('doctor_vacations')->insert($rows);
            $this->info('doctor_vacations', count($rows));
        }
    }

    // ── Inventory ────────────────────────────────────────────────────
    protected function seedInventory(): void
    {
        if (DB::table('supply_categories')->count() === 0) {
            $cats = [
                ['Medications','الأدوية','💊','#ef4444'],
                ['Medical Equipment','المعدات الطبية','🩺','#3b82f6'],
                ['Disposables','المستهلكات','🧤','#10b981'],
                ['Cosmetic Products','منتجات التجميل','💄','#ec4899'],
                ['Dental Supplies','مستلزمات الأسنان','🦷','#8b5cf6'],
                ['Laboratory Supplies','مستلزمات المختبر','🧪','#f59e0b'],
                ['Office Supplies','القرطاسية','📎','#6b7280'],
                ['Cleaning Supplies','مستلزمات النظافة','🧼','#06b6d4'],
            ];
            $rows = [];
            foreach ($cats as $i => $c) {
                $rows[] = [
                    'name_en'=>$c[0],'name_ar'=>$c[1],
                    'icon'=>$c[2],'color'=>$c[3],
                    'description'=>"Supplies category: {$c[0]}.",
                    'display_order'=>$i,'is_active'=>1,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('supply_categories')->insert($rows);
            $this->info('supply_categories', count($rows));
        }

        if (DB::table('suppliers')->count() === 0) {
            $sups = [
                ['AlFanar Medical','الفنار الطبية'],
                ['MedSupply KSA','ميد سبلاي السعودية'],
                ['Tamer Group','مجموعة تامر'],
                ['Gulf Pharmaceutical','الخليج للأدوية'],
                ['SPIMACO','سبيماكو'],
                ['Jamjoom Pharma','جمجوم فارما'],
                ['Hikma Pharmaceuticals','حكمة فارما'],
                ['Dental Depot','مستودع الأسنان'],
                ['Laser Solutions','حلول الليزر'],
                ['Office Plus','أوفيس بلس'],
                ['CleanCorp','كلين كورب'],
                ['Lab Essentials','أساسيات المختبر'],
            ];
            $rows = [];
            foreach ($sups as $i => $s) {
                $rows[] = [
                    'name_en'=>$s[0],'name_ar'=>$s[1],
                    'code'=>'SUP-'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT),
                    'phone'=>'+9661'.random_int(1000000, 9999999),
                    'email'=>strtolower(str_replace(' ','',$s[0])).'@example.com',
                    'contact_person'=>$this->fullName(),
                    'address'=>$this->addressAr(),
                    'tax_number'=>(string)random_int(300000000000000, 399999999999999),
                    'payment_terms'=>$this->pick(['NET 30','NET 60','COD','NET 15']),
                    'lead_time_days'=>random_int(2, 14),
                    'rating'=>round(random_int(35, 50)/10, 1),
                    'notes'=>null,'is_active'=>1,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('suppliers')->insert($rows);
            $this->info('suppliers', count($rows));
        }

        if (DB::table('supplies')->count() === 0) {
            $cats = DB::table('supply_categories')->get()->keyBy('name_en');
            $suppliers = DB::table('suppliers')->get();
            $items = [
                // medications
                ['Amoxicillin 500mg caps','أموكسيسيلين 500 مجم','Medications','box',200, 50, 8],
                ['Ibuprofen 400mg','إيبوبروفين 400 مجم','Medications','box', 150, 30, 5],
                ['Paracetamol 500mg','باراسيتامول 500 مجم','Medications','box', 300, 50, 4],
                ['Clindamycin 300mg','كليندامايسين 300 مجم','Medications','box', 80, 20, 15],
                ['Metronidazole 500mg','ميترونيدازول 500 مجم','Medications','box', 120, 30, 7],
                // equipment
                ['Digital Stethoscope','سماعة طبية رقمية','Medical Equipment','pcs', 8, 2, 450],
                ['BP Monitor','جهاز قياس الضغط','Medical Equipment','pcs', 12, 3, 220],
                ['Pulse Oximeter','مقياس التأكسج','Medical Equipment','pcs', 15, 3, 80],
                ['Dental Chair Light','مصباح كرسي الأسنان','Medical Equipment','pcs', 3, 1, 1200],
                // disposables
                ['Latex Gloves (L)','قفازات لاتكس (لارج)','Disposables','box', 400, 100, 25],
                ['Nitrile Gloves (M)','قفازات نيتريل (ميديم)','Disposables','box', 350, 100, 30],
                ['Surgical Masks','كمامات جراحية','Disposables','box', 500, 150, 15],
                ['Syringes 5ml','حقن 5 مل','Disposables','box', 250, 60, 20],
                ['Gauze Pads','شاش طبي','Disposables','pack', 300, 80, 12],
                ['Alcohol Swabs','مسحات كحولية','Disposables','box', 220, 60, 10],
                // cosmetic
                ['Botox Vial 100u','بوتوكس 100 وحدة','Cosmetic Products','vial', 20, 5, 850],
                ['Juvederm Voluma 1ml','جوفيديرم فولوما 1مل','Cosmetic Products','syringe', 15, 4, 1200],
                ['Restylane 1ml','ريستيلان 1مل','Cosmetic Products','syringe', 12, 3, 1100],
                ['PRP Kit','طقم بلازما','Cosmetic Products','kit', 25, 8, 320],
                ['Chemical Peel Solution','محلول تقشير كيميائي','Cosmetic Products','bottle', 10, 3, 280],
                ['Hydrafacial Serum','سيروم هيدرافيشل','Cosmetic Products','bottle', 18, 5, 380],
                // dental
                ['Composite Filling Resin','مادة حشو كومبوزيت','Dental Supplies','syringe', 40, 10, 180],
                ['Dental Anesthetic (Lidocaine)','مخدر أسنان (ليدوكائين)','Dental Supplies','box', 60, 15, 120],
                ['Zirconia Blocks','قوالب زيركون','Dental Supplies','pcs', 25, 5, 550],
                ['Dental Impression Material','مادة الطبعات','Dental Supplies','kit', 30, 8, 220],
                ['Dental Burs Set','أطقم فرايز الأسنان','Dental Supplies','set', 15, 4, 95],
                ['Endodontic Files','مبارد عصبية','Dental Supplies','pack', 35, 10, 130],
                // lab
                ['Blood Collection Tubes (EDTA)','أنابيب سحب دم','Laboratory Supplies','box', 80, 20, 45],
                ['Urinalysis Strips','شرائط تحليل البول','Laboratory Supplies','box', 50, 15, 70],
                ['Biopsy Specimen Containers','أوعية عينات خزعة','Laboratory Supplies','box', 40, 10, 35],
                // office
                ['A4 Printer Paper','ورق طابعة A4','Office Supplies','ream', 100, 25, 18],
                ['Ball Point Pens','أقلام حبر','Office Supplies','box', 50, 15, 12],
                ['Printer Toner','حبر طابعة','Office Supplies','pcs', 12, 3, 180],
                // cleaning
                ['Medical Disinfectant','مطهر طبي','Cleaning Supplies','bottle', 80, 20, 25],
                ['Floor Cleaner','منظف الأرضيات','Cleaning Supplies','bottle', 60, 15, 14],
                ['Trash Bags','أكياس قمامة','Cleaning Supplies','roll', 150, 40, 8],
            ];
            $rows = [];
            $moduleFor = ['Dental Supplies'=>'dental','Cosmetic Products'=>'cosmetic','Medications'=>'shared'];
            foreach ($items as $i => $it) {
                $cat = $cats[$it[2]] ?? null;
                $rows[] = [
                    'supply_category_id'=>$cat?->id,
                    'module'=>$moduleFor[$it[2]] ?? 'shared',
                    'name_en'=>$it[0],'name_ar'=>$it[1],
                    'sku'=>'SKU-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'barcode'=>(string)random_int(1000000000000, 9999999999999),
                    'category'=>$it[2],'unit'=>$it[3],
                    'quantity'=>$it[4] - random_int(0, (int)($it[4]*0.3)),
                    'min_quantity'=>$it[5],
                    'reorder_point'=>$it[5],
                    'reorder_quantity'=>$it[5] * 2,
                    'auto_reorder'=>(int)(random_int(0,1)),
                    'purchase_price'=>$it[6],
                    'supplier'=>null,
                    'supplier_id'=>$suppliers->random()->id,
                    'image'=>null,
                    'expiry_date'=>$it[2] === 'Medications' || $it[2] === 'Cosmetic Products' ? now()->addMonths(random_int(6,24))->toDateString() : null,
                    'batch_number'=>'BATCH-'.strtoupper($this->bothify('??####')),
                    'description'=>null,'is_active'=>1,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('supplies')->insert($rows);
            $this->info('supplies', count($rows));
        }

        if (DB::table('purchase_orders')->count() === 0) {
            $suppliers = DB::table('suppliers')->get();
            $supplies = DB::table('supplies')->get();
            $statuses = ['draft','pending_approval','approved','ordered','partially_received','received'];
            $poRows = [];
            for ($i = 0; $i < 18; $i++) {
                $status = $this->pick($statuses);
                $date = Carbon::now()->subDays(random_int(0,60));
                $poRows[] = [
                    'po_number'=>'PO-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'supplier_id'=>$suppliers->random()->id,
                    'created_by'=>$this->adminUserId,
                    'approved_by'=>in_array($status,['approved','ordered','partially_received','received']) ? $this->adminUserId : null,
                    'status'=>$status,
                    'subtotal'=>0,'tax_amount'=>0,'total'=>0,
                    'order_date'=>$date->toDateString(),
                    'expected_delivery_date'=>$date->copy()->addDays(10)->toDateString(),
                    'received_date'=>in_array($status,['received','partially_received']) ? $date->copy()->addDays(8)->toDateString() : null,
                    'notes'=>null,'delivery_notes'=>null,
                    'created_at'=>$date,'updated_at'=>now(),
                ];
            }
            DB::table('purchase_orders')->insert($poRows);

            $itemRows = [];
            foreach (DB::table('purchase_orders')->get() as $po) {
                $subtotal = 0;
                $itemCount = random_int(2,5);
                $selected = $supplies->random($itemCount);
                foreach ($selected as $sup) {
                    $qty = random_int(5, 50);
                    $unit = $sup->purchase_price;
                    $total = $qty * $unit;
                    $subtotal += $total;
                    $itemRows[] = [
                        'purchase_order_id'=>$po->id,
                        'supply_id'=>$sup->id,
                        'quantity_ordered'=>$qty,
                        'quantity_received'=>in_array($po->status,['received']) ? $qty : (in_array($po->status,['partially_received']) ? (int)($qty*0.6) : 0),
                        'unit_price'=>$unit,
                        'total_price'=>$total,
                        'batch_number'=>null,'expiry_date'=>null,'notes'=>null,
                        'created_at'=>$po->created_at,'updated_at'=>now(),
                    ];
                }
                $tax = round($subtotal * 0.15, 2);
                DB::table('purchase_orders')->where('id',$po->id)->update([
                    'subtotal'=>$subtotal,'tax_amount'=>$tax,'total'=>$subtotal + $tax,
                ]);
            }
            if ($itemRows) DB::table('purchase_order_items')->insert($itemRows);
            $this->info('purchase_orders', count($poRows));
            $this->info('purchase_order_items', count($itemRows));
        }

        if (DB::table('supply_transactions')->count() === 0) {
            $supplies = DB::table('supplies')->get();
            $rows = [];
            $types = ['purchase','usage','usage','usage','adjustment','return'];
            for ($i = 0; $i < 60; $i++) {
                $sup = $supplies->random();
                $type = $this->pick($types);
                $qty = $type === 'usage' ? -random_int(1, 8) : random_int(5, 40);
                $rows[] = [
                    'supply_id'=>$sup->id,
                    'transaction_type'=>$type,
                    'quantity'=>$qty,
                    'unit_cost'=>$sup->purchase_price,
                    'visit_id'=>$type === 'usage' && $this->visitIds ? $this->pick($this->visitIds) : null,
                    'notes'=>$type === 'usage' ? 'Used in patient visit' : 'Stock replenishment',
                    'created_by'=>$this->adminUserId,
                    'created_at'=>now()->subDays(random_int(0,90)),'updated_at'=>now(),
                ];
            }
            DB::table('supply_transactions')->insert($rows);
            $this->info('supply_transactions', count($rows));
        }
    }

    // ── CRM extras ───────────────────────────────────────────────────
    protected function seedCrm(): void
    {
        $sourceIds = DB::table('lead_sources')->pluck('id')->all();
        $campaignIds = DB::table('crm_campaigns')->pluck('id')->all();

        if (DB::table('crm_campaigns')->count() === 0) {
            $camps = [
                ['Summer Botox Campaign',30000, 'draft'],
                ['Ramadan Dental Promo',20000, 'completed'],
                ['Back-to-School Pediatric',15000, 'active'],
                ['Hair Removal Winter Special',25000, 'active'],
                ['New Year Smile Makeover',35000, 'paused'],
                ['Mother\'s Day Skincare',18000, 'completed'],
            ];
            $rows = [];
            foreach ($camps as $i => $c) {
                $start = Carbon::now()->subDays(random_int(30, 180));
                $rows[] = [
                    'name'=>$c[0],
                    'description'=>'Marketing campaign — '.$c[0],
                    'lead_source_id'=>$sourceIds ? $sourceIds[array_rand($sourceIds)] : null,
                    'status'=>$c[2],
                    'budget'=>$c[1],
                    'actual_cost'=>(int)($c[1] * (random_int(70,110)/100)),
                    'start_date'=>$start->toDateString(),
                    'end_date'=>$start->copy()->addDays(30)->toDateString(),
                    'utm_source'=>$this->pick(['facebook','instagram','google','tiktok']),
                    'utm_medium'=>'cpc',
                    'utm_campaign'=>strtolower(str_replace(' ','_',$c[0])),
                    'target_audience'=>json_encode(['age'=>'25-45','gender'=>'all','city'=>'Riyadh']),
                    'leads_count'=>random_int(20,120),
                    'conversions_count'=>random_int(5,30),
                    'created_by'=>$this->adminUserId,
                    'created_at'=>$start,'updated_at'=>now(),
                ];
            }
            DB::table('crm_campaigns')->insert($rows);
            $campaignIds = DB::table('crm_campaigns')->pluck('id')->all();
            $this->info('crm_campaigns', count($rows));
        }

        if (DB::table('leads')->count() < 40) {
            $existing = DB::table('leads')->count();
            $need = max(0, 50 - $existing);
            $statuses = ['new','contacted','qualified','appointment_booked','consultation_done','negotiation','converted','lost','dormant'];
            $modules = ['derma','dental','cosmetic','pediatric'];
            $rows = [];
            for ($i = 0; $i < $need; $i++) {
                $status = $this->pick($statuses);
                $created = Carbon::now()->subDays(random_int(0,90));
                $name = $this->firstName() . ' ' . $this->lastName();
                $rows[] = [
                    'full_name'=>$name,
                    'phone'=>'+9665'.random_int(10000000,99999999),
                    'phone2'=>null,
                    'email'=>strtolower($this->firstName()).random_int(10,99).'@example.com',
                    'gender'=>$this->pick(['male','female']),
                    'date_of_birth'=>Carbon::now()->subYears(random_int(18,55))->toDateString(),
                    'city'=>$this->pick(['Riyadh','Jeddah','Dammam','Mecca','Medina','Khobar']),
                    'nationality'=>'Saudi',
                    'status'=>$status,
                    'module'=>$this->pick($modules),
                    'priority'=>random_int(1,3),
                    'score'=>random_int(5, 95),
                    'lead_source_id'=>$sourceIds ? $sourceIds[array_rand($sourceIds)] : null,
                    'campaign_id'=>$campaignIds && random_int(0,1) ? $campaignIds[array_rand($campaignIds)] : null,
                    'referral_code'=>null,
                    'assigned_to'=>$this->staffUserIds ? $this->pick($this->staffUserIds) : null,
                    'assigned_at'=>$created->copy()->addHour(),
                    'interested_services'=>json_encode($this->maybe(['Botox','Hair Removal','Dental Cleaning','Whitening','Consultation'], random_int(1,3))),
                    'notes'=>$this->sentence(),
                    'patient_id'=>$status==='converted' && $this->patientIds ? $this->pick($this->patientIds) : null,
                    'booking_id'=>null,
                    'converted_at'=>$status==='converted' ? $created->copy()->addDays(random_int(2,15)) : null,
                    'loss_reason'=>$status==='lost' ? $this->pick(['Price','No response','Chose competitor','Not interested']) : null,
                    'lost_at'=>$status==='lost' ? $created->copy()->addDays(random_int(5,20)) : null,
                    'utm_source'=>$this->pick(['facebook','google','instagram','direct']),
                    'utm_medium'=>'cpc',
                    'utm_campaign'=>'demo',
                    'landing_page'=>'/services',
                    'ip_address'=>$this->ipv4(),
                    'last_contacted_at'=>$created->copy()->addDays(random_int(0,5)),
                    'first_contacted_at'=>$created->copy()->addHours(random_int(1,48)),
                    'next_follow_up_at'=>Carbon::now()->addDays(random_int(1,14)),
                    'follow_up_count'=>random_int(0,5),
                    'created_by'=>$this->adminUserId,
                    'created_at'=>$created,'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('leads')->insert($rows);
            $this->info('leads', count($rows));
        }

        if (DB::table('lead_activities')->count() === 0) {
            $leads = DB::table('leads')->get();
            $types = ['note','call','whatsapp','email','sms','meeting','status_change','follow_up_scheduled','follow_up_completed'];
            $outcomes = ['successful','no_answer','busy','voicemail','callback_requested','not_interested'];
            $rows = [];
            foreach ($leads as $lead) {
                $n = random_int(1, 4);
                for ($k = 0; $k < $n; $k++) {
                    $type = $this->pick($types);
                    $rows[] = [
                        'lead_id'=>$lead->id,
                        'type'=>$type,
                        'subject'=>ucfirst($type).' with lead',
                        'description'=>$this->sentence(),
                        'metadata'=>null,
                        'direction'=>$this->pick(['inbound','outbound']),
                        'duration_seconds'=>in_array($type,['call']) ? random_int(30, 600) : null,
                        'outcome'=>in_array($type,['call','whatsapp']) ? $this->pick($outcomes) : null,
                        'performed_by'=>$lead->assigned_to ?? $this->adminUserId,
                        'created_at'=>Carbon::parse($lead->created_at)->addHours($k * 12),
                        'updated_at'=>now(),
                    ];
                }
            }
            DB::table('lead_activities')->insert($rows);
            $this->info('lead_activities', count($rows));
        }

        if (DB::table('lead_follow_ups')->count() === 0) {
            $leads = DB::table('leads')->get();
            $rows = [];
            foreach ($leads as $lead) {
                for ($k = 0; $k < random_int(0, 2); $k++) {
                    $sched = Carbon::parse($lead->created_at)->addDays(random_int(1, 20));
                    $status = $sched->isPast() ? $this->pick(['completed','completed','missed','cancelled']) : 'pending';
                    $rows[] = [
                        'lead_id'=>$lead->id,
                        'assigned_to'=>$lead->assigned_to ?? $this->adminUserId,
                        'type'=>$this->pick(['call','whatsapp','email','meeting']),
                        'scheduled_at'=>$sched,
                        'completed_at'=>$status==='completed' ? $sched->copy()->addMinutes(30) : null,
                        'notes'=>$this->sentence(),
                        'result'=>$status==='completed' ? 'Lead engaged — see notes' : null,
                        'status'=>$status,
                        'reminder_sent'=>(int)random_int(0,1),
                        'created_by'=>$this->adminUserId,
                        'created_at'=>$lead->created_at,'updated_at'=>now(),
                    ];
                }
            }
            if ($rows) DB::table('lead_follow_ups')->insert($rows);
            $this->info('lead_follow_ups', count($rows));
        }

        if (DB::table('lead_stage_history')->count() === 0) {
            $leads = DB::table('leads')->get();
            $rows = [];
            foreach ($leads as $lead) {
                $stages = ['new','contacted','qualified'];
                if (in_array($lead->status, ['appointment_booked','consultation_done','negotiation','converted'])) {
                    $stages = array_merge($stages, ['appointment_booked','consultation_done']);
                }
                if ($lead->status === 'converted') $stages[] = 'converted';
                if ($lead->status === 'lost') $stages[] = 'lost';
                $prev = null;
                $t = Carbon::parse($lead->created_at);
                foreach ($stages as $s) {
                    $rows[] = [
                        'lead_id'=>$lead->id,
                        'from_status'=>$prev,
                        'to_status'=>$s,
                        'changed_by'=>$this->adminUserId,
                        'duration_minutes'=>$prev ? random_int(30, 5000) : null,
                        'changed_at'=>$t,
                        'created_at'=>$t,'updated_at'=>$t,
                    ];
                    $prev = $s;
                    $t = $t->copy()->addDays(random_int(1,5));
                }
            }
            DB::table('lead_stage_history')->insert($rows);
            $this->info('lead_stage_history', count($rows));
        }

        if (DB::table('lead_sequence_enrollments')->count() === 0) {
            $seqIds = DB::table('follow_up_sequences')->pluck('id')->all();
            if ($seqIds) {
                $leads = DB::table('leads')->inRandomOrder()->limit(18)->get();
                $rows = [];
                foreach ($leads as $lead) {
                    $rows[] = [
                        'lead_id'=>$lead->id,
                        'sequence_id'=>$this->pick($seqIds),
                        'current_step_index'=>random_int(0, 3),
                        'status'=>$this->pick(['active','active','completed','cancelled']),
                        'enrolled_at'=>$lead->created_at,
                        'next_step_at'=>now()->addDays(random_int(1,7)),
                        'completed_at'=>null,'cancelled_at'=>null,
                        'created_at'=>$lead->created_at,'updated_at'=>now(),
                    ];
                }
                DB::table('lead_sequence_enrollments')->insert($rows);
                $this->info('lead_sequence_enrollments', count($rows));
            }
        }

        if (DB::table('marketer_commissions')->count() === 0) {
            $convertedLeads = DB::table('leads')->where('status','converted')->get();
            if ($convertedLeads->count() > 0) {
                $rows = [];
                foreach ($convertedLeads as $lead) {
                    if (!$lead->assigned_to) continue;
                    $base = random_int(500, 5000);
                    $rate = random_int(5, 15);
                    $rows[] = [
                        'user_id'=>$lead->assigned_to,
                        'lead_id'=>$lead->id,
                        'booking_id'=>$lead->booking_id,
                        'payment_id'=>null,
                        'commission_type'=>'percentage',
                        'rate'=>$rate,
                        'base_amount'=>$base,
                        'commission_amount'=>round($base * $rate / 100, 2),
                        'status'=>$this->pick(['pending','approved','paid']),
                        'paid_date'=>random_int(0,1) ? now()->subDays(random_int(1,30))->toDateString() : null,
                        'notes'=>null,
                        'approved_by'=>$this->adminUserId,
                        'created_at'=>$lead->converted_at ?? now(),'updated_at'=>now(),
                    ];
                }
                if ($rows) DB::table('marketer_commissions')->insert($rows);
                $this->info('marketer_commissions', count($rows));
            }
        }
    }

    // ── Patient extras ───────────────────────────────────────────────
    protected function seedPatientExtras(): void
    {
        if (DB::table('patient_vitals')->count() === 0) {
            $visits = DB::table('visits')->get();
            $rows = [];
            foreach ($visits as $v) {
                $bmi = round(random_int(180, 320)/10, 1);
                $rows[] = [
                    'patient_id'=>$v->patient_id,
                    'visit_id'=>$v->id,
                    'recorded_by'=>$this->adminUserId,
                    'blood_pressure_systolic'=>random_int(100, 140),
                    'blood_pressure_diastolic'=>random_int(60, 90),
                    'heart_rate'=>random_int(60, 100),
                    'temperature'=>round(random_int(360, 378)/10, 1),
                    'respiratory_rate'=>random_int(12, 20),
                    'oxygen_saturation'=>round(random_int(950, 1000)/10, 1),
                    'weight'=>round(random_int(450, 1100)/10, 1),
                    'height'=>round(random_int(1500, 1900)/10, 1),
                    'bmi'=>$bmi,
                    'blood_sugar'=>random_int(0,1) ? random_int(80, 180) : null,
                    'blood_sugar_type'=>random_int(0,1) ? $this->pick(['fasting','random','post_meal']) : null,
                    'pain_level'=>random_int(0, 7),
                    'pain_location'=>null,
                    'notes'=>null,
                    'source'=>'manual',
                    'recorded_at'=>$v->visit_date.' '.sprintf('%02d:00:00', random_int(9,16)),
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('patient_vitals')->insert($rows);
            $this->info('patient_vitals', count($rows));
        }

        if (DB::table('patient_photos')->count() === 0) {
            $rows = [];
            foreach (array_slice($this->patientIds, 0, 8) as $pid) {
                $rows[] = [
                    'patient_id'=>$pid,
                    'photo_path'=>'demo/patients/profile_'.$pid.'.jpg',
                    'caption'=>'Profile photo',
                    'taken_at'=>now()->subDays(random_int(10,120))->toDateString(),
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('patient_photos')->insert($rows);
            $this->info('patient_photos', count($rows));
        }

        if (DB::table('patient_documents')->count() === 0) {
            $types = ['national_id','insurance_card','medical_report','lab_result','consent_form','prescription'];
            $rows = [];
            foreach ($this->patientIds as $pid) {
                foreach (array_slice($types, 0, random_int(1,3)) as $t) {
                    $rows[] = [
                        'patient_id'=>$pid,
                        'uploaded_by'=>$this->adminUserId,
                        'document_type'=>$t,
                        'title'=>ucfirst(str_replace('_',' ',$t)),
                        'file_path'=>"demo/documents/{$t}_{$pid}.pdf",
                        'original_name'=>"{$t}-{$pid}.pdf",
                        'mime_type'=>'application/pdf',
                        'file_size'=>random_int(50000, 500000),
                        'document_date'=>now()->subDays(random_int(10,365))->toDateString(),
                        'expiry_date'=>$t==='national_id' ? now()->addYears(5)->toDateString() : null,
                        'notes'=>null,'is_confidential'=>0,'source'=>'admin',
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            DB::table('patient_documents')->insert($rows);
            $this->info('patient_documents', count($rows));
        }

        if (DB::table('patient_satisfactions')->count() === 0) {
            $visits = DB::table('visits')->where('status','completed')->get();
            $rows = [];
            foreach ($visits->take(22) as $v) {
                $overall = random_int(3, 5);
                $rows[] = [
                    'patient_id'=>$v->patient_id,
                    'visit_id'=>$v->id,
                    'doctor_id'=>$v->doctor_id,
                    'booking_id'=>$v->booking_id,
                    'overall_rating'=>$overall,
                    'doctor_rating'=>random_int(3, 5),
                    'staff_rating'=>random_int(3, 5),
                    'cleanliness_rating'=>random_int(4, 5),
                    'waiting_time_rating'=>random_int(2, 5),
                    'communication_rating'=>random_int(3, 5),
                    'comments'=>$this->pick([
                        'خدمة ممتازة وطاقم محترم','تجربة رائعة','وقت الانتظار طويل قليلاً',
                        'أنصح بالعيادة','الطبيب متمكن جداً','Great service, will return!',
                    ]),
                    'would_recommend'=>$overall >= 4 ? 1 : 0,
                    'improvement_areas'=>json_encode($this->maybe(['waiting_time','parking','online_booking'], random_int(0,2))),
                    'nps_score'=>$overall >= 4 ? random_int(8,10) : random_int(3,7),
                    'source'=>$this->pick(['portal','sms','tablet']),
                    'token'=>null,'is_anonymous'=>0,
                    'created_at'=>$v->completed_at ?? $v->created_at,'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('patient_satisfactions')->insert($rows);
            $this->info('patient_satisfactions', count($rows));
        }

        if (DB::table('patient_wallets')->count() === 0) {
            $rows = [];
            foreach (array_slice($this->patientIds, 0, 12) as $pid) {
                $rows[] = [
                    'patient_id'=>$pid,
                    'balance'=>random_int(0, 5000),
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('patient_wallets')->insert($rows);
            $this->info('patient_wallets', count($rows));

            $txRows = [];
            foreach (DB::table('patient_wallets')->get() as $w) {
                $balance = 0;
                $n = random_int(2, 5);
                for ($k = 0; $k < $n; $k++) {
                    $type = $this->pick(['deposit','deposit','payment','refund_credit']);
                    $amount = random_int(100, 1500);
                    if ($type === 'payment') $amount = -abs($amount);
                    $balance += $amount;
                    if ($balance < 0) $balance = 0;
                    $txRows[] = [
                        'patient_id'=>$w->patient_id,
                        'type'=>$type,
                        'amount'=>abs($amount),
                        'balance_after'=>$balance,
                        'description'=>$this->pick(['Cash deposit','Invoice payment','Refund credit','Adjustment']),
                        'reference_type'=>null,'reference_id'=>null,
                        'created_by'=>$this->adminUserId,
                        'notes'=>null,
                        'created_at'=>now()->subDays(random_int(0,60)),'updated_at'=>now(),
                    ];
                }
                DB::table('patient_wallets')->where('id',$w->id)->update(['balance'=>$balance]);
            }
            DB::table('wallet_transactions')->insert($txRows);
            $this->info('wallet_transactions', count($txRows));
        }

        if (DB::table('patient_recall_reminders')->count() === 0) {
            $rows = [];
            foreach (array_slice($this->patientIds, 0, 14) as $pid) {
                $rows[] = [
                    'patient_id'=>$pid,
                    'module'=>$this->pick(['dental','derma','pediatric']),
                    'type'=>$this->pick(['checkup','cleaning','followup','vaccination']),
                    'last_visit_date'=>now()->subMonths(random_int(3, 12))->toDateString(),
                    'reminder_sent_at'=>random_int(0,1) ? now()->subDays(random_int(1,15)) : null,
                    'sms_status'=>$this->pick(['sent','pending','failed',null]),
                    'notes'=>null,
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('patient_recall_reminders')->insert($rows);
            $this->info('patient_recall_reminders', count($rows));
        }

        if (DB::table('visit_photos')->count() === 0) {
            $visits = DB::table('visits')->inRandomOrder()->limit(18)->get();
            $rows = [];
            foreach ($visits as $v) {
                $rows[] = [
                    'visit_id'=>$v->id,
                    'photo_path'=>"demo/visits/visit_{$v->id}.jpg",
                    'photo_type'=>$this->pick(['before','after','during']),
                    'caption'=>'Visit documentation',
                    'created_at'=>now(),'updated_at'=>now(),
                ];
            }
            DB::table('visit_photos')->insert($rows);
            $this->info('visit_photos', count($rows));
        }

        if (DB::table('doctor_favorite_patients')->count() === 0) {
            $rows = [];
            foreach ($this->doctorIds as $did) {
                $pats = collect($this->patientIds)->shuffle()->take(random_int(3,5));
                foreach ($pats as $pid) {
                    $rows[] = ['doctor_id'=>$did,'patient_id'=>$pid,'created_at'=>now()];
                }
            }
            DB::table('doctor_favorite_patients')->insert($rows);
            $this->info('doctor_favorite_patients', count($rows));
        }

        if (DB::table('doctor_patient_notes')->count() === 0) {
            $rows = [];
            for ($i = 0; $i < 18; $i++) {
                $rows[] = [
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'patient_id'=>$this->pick($this->patientIds),
                    'note'=>$this->pick([
                        'Patient allergic to penicillin','Prefers afternoon appointments',
                        'History of dental anxiety — take extra time','Excellent oral hygiene',
                        'Needs follow-up in 3 months','Requires pre-medication before procedures',
                    ]),
                    'is_pinned'=>(int)(random_int(0,2)===0),
                    'created_at'=>now()->subDays(random_int(0,60)),'updated_at'=>now(),
                ];
            }
            DB::table('doctor_patient_notes')->insert($rows);
            $this->info('doctor_patient_notes', count($rows));
        }
    }

    // ── Other ────────────────────────────────────────────────────────
    protected function seedOther(): void
    {
        if (DB::table('online_consultations')->count() < 5) {
            $rows = [];
            $statuses = ['scheduled','completed','completed','completed','missed_patient','cancelled'];
            for ($i = 0; $i < 12; $i++) {
                $date = Carbon::now()->addDays(random_int(-60, 30));
                $status = $date->isPast() ? $this->pick($statuses) : 'scheduled';
                $pid = $this->pick($this->patientIds);
                $did = $this->pick($this->doctorIds);
                $rows[] = [
                    'consultation_number'=>'OC-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'patient_id'=>$pid,'doctor_id'=>$did,
                    'booking_id'=>null,'booking_appointment_id'=>null,'visit_id'=>null,'invoice_id'=>null,
                    'scheduled_date'=>$date->toDateString(),
                    'start_time'=>sprintf('%02d:00:00', random_int(9,17)),
                    'end_time'=>sprintf('%02d:30:00', random_int(9,17)),
                    'module'=>$this->pick(['derma','dental','pediatric','cosmetic']),
                    'consultation_type'=>$this->pick(['initial','followup']),
                    'agora_channel_name'=>'demo-channel-'.$i,
                    'agora_doctor_token'=>null,'agora_patient_token'=>null,'agora_tokens_expire_at'=>null,
                    'doctor_joined_at'=>$status==='completed' ? $date : null,
                    'patient_joined_at'=>$status==='completed' ? $date : null,
                    'session_started_at'=>$status==='completed' ? $date : null,
                    'session_ended_at'=>$status==='completed' ? $date->copy()->addMinutes(25) : null,
                    'duration_seconds'=>$status==='completed' ? random_int(300, 1800) : null,
                    'status'=>$status,
                    'chief_complaint'=>$this->pick(['Skin rash','Follow-up check','Child fever','Dental pain']),
                    'diagnosis'=>$status==='completed' ? $this->sentence() : null,
                    'doctor_notes'=>$status==='completed' ? 'Consultation proceeded smoothly.' : null,
                    'patient_feedback'=>$status==='completed' && random_int(0,1) ? 'Very helpful' : null,
                    'patient_rating'=>$status==='completed' && random_int(0,1) ? random_int(3,5) : null,
                    'recording_enabled'=>0,'recording_url'=>null,'recording_resource_id'=>null,
                    'fee'=>random_int(150, 400),
                    'payment_status'=>$this->pick(['paid','paid','pending','unpaid']),
                    'payment_gateway_reference'=>'GW-'.strtoupper($this->bothify('??####')),
                    'cancellation_reason'=>$status==='cancelled' ? 'Patient requested reschedule' : null,
                    'cancelled_by'=>$status==='cancelled' ? $this->adminUserId : null,
                    'cancelled_at'=>$status==='cancelled' ? $date : null,
                    'created_at'=>now()->subDays(random_int(1,60)),'updated_at'=>now(),
                ];
            }
            DB::table('online_consultations')->insert($rows);
            $this->info('online_consultations', count($rows));
        }

        if (DB::table('payment_transactions')->count() === 0) {
            $rows = [];
            for ($i = 0; $i < 18; $i++) {
                $amount = random_int(150, 1500);
                $status = $this->pick(['succeeded','succeeded','pending','failed','refunded']);
                $rows[] = [
                    'transaction_id'=>'TX-'.date('Ymd').'-'.random_int(100000,999999),
                    'gateway'=>$this->pick(['paymob','paytabs','stripe']),
                    'gateway_reference'=>'GW-'.strtoupper($this->bothify('??####??')),
                    'intent_id'=>'intent_'.random_int(10000,99999),
                    'online_consultation_id'=>null,
                    'invoice_id'=>$this->invoiceIds ? $this->pick($this->invoiceIds) : null,
                    'patient_id'=>$this->pick($this->patientIds),
                    'amount'=>$amount,'currency'=>'EGP',
                    'status'=>$status,'type'=>'payment',
                    'gateway_request'=>json_encode(['amount'=>$amount]),
                    'gateway_response'=>json_encode(['status'=>$status]),
                    'failure_reason'=>$status==='failed' ? 'Card declined' : null,
                    'checkout_url'=>null,
                    'paid_at'=>$status==='succeeded' ? now()->subDays(random_int(0,60)) : null,
                    'failed_at'=>$status==='failed' ? now()->subDays(random_int(0,60)) : null,
                    'created_at'=>now()->subDays(random_int(0,60)),'updated_at'=>now(),
                ];
            }
            DB::table('payment_transactions')->insert($rows);
            $this->info('payment_transactions', count($rows));
        }

        if (DB::table('referrals')->count() === 0) {
            $rows = [];
            $deptNames = ['derma','dental','pediatric','cosmetic','general','lab','xray'];
            for ($i = 0; $i < 10; $i++) {
                $toDr = $this->pick($this->doctorIds);
                $fromDr = $this->pick($this->doctorIds);
                if ($fromDr === $toDr && count($this->doctorIds) > 1) {
                    $fromDr = $this->pick(array_diff($this->doctorIds, [$toDr]));
                }
                $rows[] = [
                    'referral_number'=>'REF-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'patient_id'=>$this->pick($this->patientIds),
                    'referring_doctor_id'=>$fromDr,
                    'referred_to_doctor_id'=>$toDr,
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'from_department'=>$this->pick($deptNames),
                    'to_department'=>$this->pick($deptNames),
                    'urgency'=>$this->pick(['routine','urgent','stat']),
                    'status'=>$this->pick(['pending','accepted','completed','declined']),
                    'reason'=>$this->pick(['Specialist opinion','Further evaluation','Specific treatment needed','Co-management']),
                    'clinical_notes'=>$this->sentence(),
                    'referring_diagnosis'=>$this->pick(['Chronic dermatitis','Suspicious lesion','Dental abscess','Growth concern']),
                    'provisional_diagnosis'=>$this->word(),
                    'response_notes'=>null,
                    'accepted_at'=>now()->subDays(random_int(1,10)),
                    'scheduled_at'=>now()->addDays(random_int(1,14)),
                    'completed_at'=>null,'declined_at'=>null,'resulting_visit_id'=>null,
                    'created_at'=>now()->subDays(random_int(1,30)),'updated_at'=>now(),
                ];
            }
            DB::table('referrals')->insert($rows);
            $this->info('referrals', count($rows));
        }

        if (DB::table('medical_certificates')->count() === 0) {
            $types = ['sick_leave','fitness','medical_report','referral_letter','follow_up'];
            $rows = [];
            for ($i = 0; $i < 10; $i++) {
                $issue = Carbon::now()->subDays(random_int(1,90));
                $days = random_int(1, 7);
                $rows[] = [
                    'certificate_number'=>'MC-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'patient_id'=>$this->pick($this->patientIds),
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'visit_id'=>$this->visitIds ? $this->pick($this->visitIds) : null,
                    'created_by'=>$this->adminUserId,
                    'type'=>$this->pick($types),
                    'issue_date'=>$issue->toDateString(),
                    'start_date'=>$issue->toDateString(),
                    'end_date'=>$issue->copy()->addDays($days)->toDateString(),
                    'days'=>$days,
                    'diagnosis'=>$this->pick(['Acute pharyngitis','Migraine','Post-surgical recovery','Dental procedure recovery']),
                    'notes'=>'Rest recommended.',
                    'recommendations'=>'Avoid strenuous activity; follow-up as needed.',
                    'status'=>'issued',
                    'issued_at'=>$issue,
                    'created_at'=>$issue,'updated_at'=>now(),
                ];
            }
            DB::table('medical_certificates')->insert($rows);
            $this->info('medical_certificates', count($rows));
        }

        if (DB::table('treatment_plans')->count() === 0) {
            $rows = [];
            for ($i = 0; $i < 9; $i++) {
                $start = Carbon::now()->subDays(random_int(7, 90));
                $rows[] = [
                    'patient_id'=>$this->pick($this->patientIds),
                    'doctor_id'=>$this->pick($this->doctorIds),
                    'title'=>$this->pick(['Acne treatment protocol','Full mouth rehabilitation','Orthodontic alignment','Hair restoration plan','Skin brightening regimen']),
                    'description'=>$this->sentence(),
                    'goals'=>'Improve patient outcome with multi-session coordinated care.',
                    'status'=>$this->pick(['active','active','completed','draft']),
                    'start_date'=>$start->toDateString(),
                    'end_date'=>$start->copy()->addMonths(random_int(2,6))->toDateString(),
                    'notes'=>null,'created_by'=>$this->adminUserId,
                    'created_at'=>$start,'updated_at'=>now(),
                ];
            }
            DB::table('treatment_plans')->insert($rows);
            $this->info('treatment_plans', count($rows));

            $stepRows = [];
            foreach (DB::table('treatment_plans')->get() as $tp) {
                $steps = random_int(3, 5);
                for ($k = 1; $k <= $steps; $k++) {
                    $completed = $k <= random_int(0, $steps);
                    $stepRows[] = [
                        'treatment_plan_id'=>$tp->id,
                        'service_id'=>null,
                        'step_order'=>$k,
                        'title'=>"Step {$k} — ".$this->pick(['Consultation','Preparation','Main procedure','Follow-up','Final evaluation']),
                        'description'=>$this->sentence(),
                        'sessions_required'=>1,
                        'sessions_completed'=>$completed ? 1 : 0,
                        'estimated_cost'=>random_int(300, 3000),
                        'status'=>$completed ? 'completed' : 'pending',
                        'scheduled_date'=>Carbon::parse($tp->start_date)->addWeeks($k)->toDateString(),
                        'completed_date'=>$completed ? Carbon::parse($tp->start_date)->addWeeks($k)->toDateString() : null,
                        'notes'=>null,'visit_id'=>null,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
            }
            DB::table('treatment_plan_steps')->insert($stepRows);
            $this->info('treatment_plan_steps', count($stepRows));
        }

        // treatment_plan_consents requires dental_treatment_plans
        if (DB::table('treatment_plan_consents')->count() === 0) {
            $dtps = DB::table('dental_treatment_plans')->get();
            if ($dtps->count() > 0) {
                $rows = [];
                foreach ($dtps as $tp) {
                    $rows[] = [
                        'dental_treatment_plan_id'=>$tp->id,
                        'patient_id'=>$tp->patient_id,
                        'sent_by'=>$this->adminUserId,
                        'status'=>$this->pick(['pending','signed','signed']),
                        'sent_at'=>now()->subDays(random_int(2,20)),
                        'signed_at'=>now()->subDays(random_int(0,5)),
                        'declined_at'=>null,
                        'expires_at'=>now()->addDays(30),
                        'signature_image_path'=>'demo/signatures/tp_'.$tp->id.'.png',
                        'patient_ip'=>$this->ipv4(),
                        'patient_user_agent'=>'Mozilla/5.0',
                        'consent_text_snapshot'=>json_encode(['version'=>1,'text'=>'Full treatment plan consent snapshot for demo purposes.','procedures'=>['Consultation','Treatment','Follow-up']]),
                        'risks_notes'=>'Standard procedural risks discussed.',
                        'pdf_path'=>null,'declined_reason'=>null,
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
                DB::table('treatment_plan_consents')->insert($rows);
                $this->info('treatment_plan_consents', count($rows));
            }
        }

        if (DB::table('expenses')->count() === 0) {
            $cats = DB::table('expense_categories')->get();
            if ($cats->count() === 0) {
                $this->command->warn('    expense_categories empty — skipping expenses');
            } else {
                $rows = [];
                $descs = ['Office supplies','Utility bill','Staff lunch','Equipment maintenance','Software subscription',
                         'Marketing materials','Cleaning service','Courier','Bank fees','Miscellaneous'];
                for ($i = 0; $i < 25; $i++) {
                    $rows[] = [
                        'expense_category_id'=>$cats->random()->id,
                        'expense_item_id'=>null,
                        'amount'=>random_int(50, 3000),
                        'expense_date'=>now()->subDays(random_int(0,90))->toDateString(),
                        'description'=>$this->pick($descs),
                        'receipt_photo'=>null,
                        'is_recurring'=>(int)(random_int(0,3)===0),
                        'recurring_period'=>random_int(0,3)===0 ? 'monthly' : null,
                        'created_by'=>$this->adminUserId,
                        'created_at'=>now()->subDays(random_int(0,90)),'updated_at'=>now(),
                    ];
                }
                DB::table('expenses')->insert($rows);
                $this->info('expenses', count($rows));
            }
        }

        if (DB::table('credit_notes')->count() === 0 && $this->invoiceIds) {
            $rows = [];
            for ($i = 0; $i < 7; $i++) {
                $invId = $this->pick($this->invoiceIds);
                $inv = DB::table('invoices')->find($invId);
                if (!$inv) continue;
                $rows[] = [
                    'credit_note_number'=>'CN-'.date('Y').'-'.str_pad((string)($i+1),5,'0',STR_PAD_LEFT),
                    'invoice_id'=>$invId,
                    'patient_id'=>$inv->patient_id,
                    'created_by'=>$this->adminUserId,
                    'approved_by'=>$this->adminUserId,
                    'type'=>$this->pick(['partial_refund','full_refund','adjustment']),
                    'status'=>$this->pick(['approved','refunded','pending_approval']),
                    'amount'=>round($inv->total * 0.3, 2),
                    'reason'=>$this->pick(['Patient complaint','Service not delivered','Billing error','Goodwill adjustment']),
                    'notes'=>null,
                    'refund_method'=>$this->pick(['cash','card','wallet_credit']),
                    'approved_at'=>now()->subDays(random_int(1,20)),
                    'refunded_at'=>now()->subDays(random_int(0,15)),
                    'created_at'=>now()->subDays(random_int(1,30)),'updated_at'=>now(),
                ];
            }
            if ($rows) DB::table('credit_notes')->insert($rows);
            $this->info('credit_notes', count($rows));
        }

        if (DB::table('discount_usage')->count() === 0) {
            $codeIds = DB::table('discount_codes')->pluck('id')->all();
            if ($codeIds && $this->invoiceIds) {
                $rows = [];
                for ($i = 0; $i < 12; $i++) {
                    $invId = $this->pick($this->invoiceIds);
                    $inv = DB::table('invoices')->find($invId);
                    if (!$inv) continue;
                    $rows[] = [
                        'discount_code_id'=>$this->pick($codeIds),
                        'patient_id'=>$inv->patient_id,
                        'invoice_id'=>$invId,
                        'booking_id'=>$inv->booking_id,
                        'package_bundle_booking_id'=>null,
                        'discount_amount'=>round($inv->total * 0.1, 2),
                        'ip_address'=>$this->ipv4(),
                        'user_agent'=>'Mozilla/5.0 (demo)',
                        'created_at'=>$inv->created_at,'updated_at'=>now(),
                    ];
                }
                if ($rows) DB::table('discount_usage')->insert($rows);
                $this->info('discount_usage', count($rows));
            }
        }
    }
}
