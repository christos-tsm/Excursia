# Role-Based Dashboards

Αυτό το έγγραφο εξηγεί πώς λειτουργεί το σύστημα των role-based dashboards στην εφαρμογή.

## Επισκόπηση

Το σύστημα παρέχει διαφορετικά dashboards βάσει του ρόλου του χρήστη μέσα σε ένα tenant (τουριστικό γραφείο). Κάθε ρόλος έχει διαφορετικές δυνατότητες και περιεχόμενο dashboard.

## Ρόλοι Χρηστών

### 1. Owner (Ιδιοκτήτης)
- **Περιγραφή**: Ο ιδιοκτήτης του τουριστικού γραφείου
- **Δικαιώματα**: Πλήρη πρόσβαση σε όλες τις λειτουργίες
- **Dashboard**: `OwnerDashboard.tsx` - Πλήρες dashboard με όλες τις λειτουργίες

**Χαρακτηριστικά:**
- Στατιστικά και analytics
- Διαχείριση ταξιδιών
- Διαχείριση προσωπικού (προσκλήσεις)
- Οικονομικά στοιχεία
- Ρυθμίσεις γραφείου
- Αναφορές

### 2. Guide (Ξεναγός)
- **Περιγραφή**: Ξεναγός που οδηγεί τα ταξίδια
- **Δικαιώματα**: Πρόσβαση σε ταξίδια και προγράμματα
- **Dashboard**: `GuideDashboard.tsx` - Εστιασμένο σε ταξίδια και ξεναγήσεις

**Χαρακτηριστικά:**
- Τα ταξίδια που έχει ανατεθεί
- Πρόγραμμα εβδομάδας
- Διαχείριση ομάδων ταξιδιωτών
- Πόροι και υλικό ξενάγησης
- Συμβουλές για ξεναγούς

### 3. Staff (Προσωπικό)
- **Περιγραφή**: Γενικό προσωπικό του γραφείου
- **Δικαιώματα**: Βασικές λειτουργίες προβολής και επικοινωνίας
- **Dashboard**: `StaffDashboard.tsx` - Απλοποιημένο dashboard για βασικές εργασίες

**Χαρακτηριστικά:**
- Προβολή ταξιδιών (μόνο ανάγνωση)
- Καθήκοντα και εργασίες
- Επικοινωνία με την ομάδα
- Αναφορές εργασίας
- Σημαντικές επαφές

## Τεχνική Υλοποίηση

### 1. Route Configuration
```php
// routes/web.php
Route::get('/dashboard', function ($tenant_id) {
    $user = Auth::user();
    $tenant = \App\Models\Tenant::findOrFail($tenant_id);
    $userRoles = $user->roles->pluck('name')->toArray();

    return Inertia::render('Tenant/Dashboard', [
        'tenant' => $tenant,
        'userRoles' => $userRoles,
    ]);
})->name('tenant.dashboard');
```

### 2. Dashboard Component Selection
```tsx
// Dashboard.tsx
const renderDashboardContent = () => {
    if (userRoles.includes('owner')) {
        return <OwnerDashboard tenant={tenant} userName={auth.user.name} />;
    }
    
    if (userRoles.includes('guide')) {
        return <GuideDashboard tenant={tenant} userName={auth.user.name} />;
    }
    
    return <StaffDashboard tenant={tenant} userName={auth.user.name} />;
};
```

### 3. Navigation Menu
Το navigation menu προσαρμόζεται επίσης βάσει του ρόλου στο `AuthenticatedLayout.tsx`:

- **Owner**: Πλήρες menu με όλες τις επιλογές
- **Guide**: Εστιασμένο σε ταξίδια και προγράμματα
- **Staff**: Βασικές λειτουργίες

### 4. Utility Functions
```tsx
// Utils/roles.ts
import { getUserRoles, isOwner, isGuide, isStaff } from '@/Utils/roles';

// Χρήση:
const userRoles = getUserRoles(user);
const isUserOwner = isOwner(user);
```

## Αρχεία που Δημιουργήθηκαν/Τροποποιήθηκαν

### Νέα Αρχεία:
- `resources/js/Pages/Tenant/Dashboard/OwnerDashboard.tsx`
- `resources/js/Pages/Tenant/Dashboard/GuideDashboard.tsx`
- `resources/js/Pages/Tenant/Dashboard/StaffDashboard.tsx`
- `resources/js/Utils/roles.ts`

### Τροποποιημένα Αρχεία:
- `routes/web.php` - Προσθήκη userRoles στο dashboard route
- `resources/js/Pages/Tenant/Dashboard.tsx` - Logic για επιλογή dashboard
- `resources/js/Layouts/AuthenticatedLayout.tsx` - Role-based navigation

## Μελλοντικές Επεκτάσεις

1. **Προσθήκη νέων ρόλων**: Εύκολη προσθήκη νέων ρόλων (π.χ. 'manager', 'accountant')
2. **Permissions**: Πιο λεπτομερής έλεγχος δικαιωμάτων μέσα σε κάθε ρόλο
3. **Dynamic content**: Φόρτωση δεδομένων βάσει ρόλου (π.χ. στατιστικά μόνο για owners)
4. **Mobile optimization**: Βελτιστοποίηση για mobile συσκευές

## Πώς να Προσθέσετε Νέο Ρόλο

1. **Δημιουργήστε νέο Dashboard Component**:
   ```tsx
   // ManagerDashboard.tsx
   export default function ManagerDashboard({ tenant, userName }) {
       // Dashboard logic για manager
   }
   ```

2. **Ενημερώστε το κύριο Dashboard**:
   ```tsx
   if (userRoles.includes('manager')) {
       return <ManagerDashboard tenant={tenant} userName={auth.user.name} />;
   }
   ```

3. **Προσθέστε navigation items**:
   ```tsx
   // AuthenticatedLayout.tsx
   if (userRoles.includes('manager')) {
       return [...managerRoutes];
   }
   ```

4. **Ενημερώστε permissions**:
   ```php
   // RolesAndPermissionsSeeder.php
   $managerRole = Role::create(['name' => 'manager']);
   $managerRole->givePermissionTo([...permissions]);
   ```

## Ασφάλεια

- Όλοι οι έλεγχοι ρόλων γίνονται τόσο στο frontend όσο και στο backend
- Τα middleware ελέγχουν την πρόσβαση σε κάθε route
- Τα δικαιώματα ελέγχονται με το Spatie Permission package

## Troubleshooting

### Ο χρήστης δεν βλέπει το σωστό dashboard
1. Ελέγξτε αν έχει ανατεθεί ο σωστός ρόλος
2. Ελέγξτε αν τα roles φορτώνονται σωστά στο frontend
3. Ελέγξτε τη λογική στο `renderDashboardContent()`

### Navigation δεν εμφανίζεται σωστά
1. Ελέγξτε τη συνάρτηση `getTenantRoutes()`
2. Ελέγξτε αν τα user roles φορτώνονται στο `AuthenticatedLayout`

### Σφάλματα permissions
1. Ελέγξτε τα middleware στα routes
2. Ελέγξτε αν τα permissions έχουν ανατεθεί σωστά στους ρόλους 