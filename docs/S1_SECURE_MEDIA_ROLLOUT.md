# S1 — Secure media serving for PHI files (rollout)

**Branch:** `security/s1-media-serving` (NOT merged — verify on staging first)
**Why:** every sensitive patient file is on the world-readable `public` disk and
downloadable at a guessable `/storage/...` URL with no auth — a real PHI leak.

## Phase 0 — Foundation (DONE on this branch)
- `app/Http/Controllers/MediaController.php` — streams a file only for an
  authenticated session via a **signed** URL; whitelists disks; blocks
  traversal; transitional fallback to the public disk for not-yet-migrated files.
- `app/Support/SecureMedia.php` — `SecureMedia::url($path, $disk='local')`
  returns a 60-min temporary signed `media.show` URL.
- Route `GET /media` (`media.show`, `signed` middleware) in `routes/web.php`.
- Tests: `tests/Feature/Security/SecureMediaTest.php` (auth streams; anon 403;
  tampered/unsigned 403; traversal/bad-disk 404). ✅

## Phase 1 — Switch uploads to the private (`local`) disk
For each upload site, change `->store(..., 'public')` / `Storage::disk('public')`
→ `'local'` (keep the same path prefix). Sites (controller@line → path → column):
- DoctorDentalXrayController:50 / Admin DentalXrayController:64 → `dental-xrays` → dental_xrays.image_path
- DoctorDentalComparisonController:51,54 / Admin:82,85 → `dental-comparisons` → before/after_image_path
- Admin DentalChartController:141 → `uploads/dental/entries/{patient}` → dental_chart_entries.media[]
- DoctorDermaController:153 / Admin DermaPhotoController:43 → `derma/photos` → derma_photos.image_path
- Admin CosmeticConsentController:62 → `cosmetic/signatures` → cosmetic_consents.signature_path
- Admin CosmeticPhotoController:43 → `cosmetic/photos` → cosmetic_photos.image_path
- DoctorVisitController:241 / Admin VisitController:183 → `visit-photos` → visit_photos.photo_path
- Admin PatientDocumentController:38 / Patient:90 → `patient-documents/{patient}` → patient_documents.file_path
- Secretary SecretaryBookingController:355 / Admin BookingController:490 → `consents/{booking}` → booking_consents.file_path
- Admin PatientInsuranceController:125,143 → `insurance-cards` → patient_insurance.front/back_image
- Patient profile photo (Secretary/Admin/Patient) → `uploads/patients` / `patient-photos` → patients.photo
- (Optional, lower-sensitivity) ChatController:166 → `uploads/messages`
NON-SENSITIVE — leave on public: doctor profile photos, gallery, posts, sliders, logos.

## Phase 2 — Serve via signed URLs (model accessors)
Add a `*_secure_url` accessor to each model that returns `SecureMedia::url($this->path_column)`,
and expose it in the Inertia payloads. Then convert every display site (replace
`'/storage/'+path` with the accessor). Display sites (file:line):
- Patient: Visits/Show.vue:161, Photos/Index.vue:61,95, Dental/Xrays.vue:47,79,
  Dental/Overview.vue:362, Components/Patient/Tabs/DermaTab.vue:93,105,117,188,
  Documents/Index.vue:200 (already a download route).
- Doctor: Visits/Show.vue:479,490,502,598, Derma/Patients/Show.vue:139,
  Dental/DentalChart/Search.vue:78.
- Admin: Dental/DentalChart/Show.vue:783-789, Dental/DentalChart/Search.vue:92,
  Visits/Show.vue:740,790, Derma/Gallery.vue:82, Cosmetic/Gallery.vue:83.
Keep `photo.secure_url || ('/storage/'+path)` during transition so nothing breaks
before Phase 3.

## Phase 3 — Migrate existing files + close public access
- Artisan command: move existing files for the prefixes above from
  `storage/app/public/<prefix>` → `storage/app/private/<prefix>` (idempotent).
- After verifying display works via signed URLs, deny direct web access to the
  migrated subtrees (or confirm they no longer exist on the public disk).
- Existing download controllers (PatientDocument::download etc.) read the
  `local` disk (with public fallback during transition).

## Phase 4 — Verify on staging, then deploy
- Tests green (Phase 0 done; add per-model accessor + ownership tests in P2).
- On **staging**: confirm doctors/patients still SEE x-rays/photos (signed URLs),
  and that a logged-out direct `/storage/<migrated>` hit 404s.
- Then merge `security/s1-media-serving` → main.

## Notes
- Signed + authenticated session = anonymous enumeration is closed (the reported
  hole). For stricter per-record ownership on the media route itself, the signed
  URL is only generated on pages the viewer is already authorized to see; a
  follow-up can add explicit per-model ownership checks keyed by path prefix.
