<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ROLES & PERMISSIONS
        |--------------------------------------------------------------------------
        */
        $this->call(RoleSeeder::class);
        $this->call(PrismRoleSeeder::class);

        /*
        |--------------------------------------------------------------------------
        | ORGANIZATION
        |--------------------------------------------------------------------------
        */
        $organization = Organization::factory()->create([
            'name' => 'Northstar Operations Group',
            'email' => 'hello@northstar.test',
            'phone' => '+1 555 0147',
            'description' => 'PRISM demonstration organization for project delivery teams.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | DIVISIONS
        |--------------------------------------------------------------------------
        */
        $divisions = collect([
            [
                'name' => 'Operations',
                'description' => 'Business operations and delivery coordination.',
            ],
            [
                'name' => 'Engineering',
                'description' => 'Product engineering and technical delivery.',
            ],
            [
                'name' => 'Customer Success',
                'description' => 'Customer enablement and service quality.',
            ],
            [
                'name' => 'Finance',
                'description' => 'Planning, controls, and financial operations.',
            ],
            [
                'name' => 'Product',
                'description' => 'Product planning, research, and delivery.',
            ],
        ])->map(
            fn (array $data) => Division::factory()->create($data)
        );

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        |
        | DEMO ACCOUNT
        |
        | Email    : superadmin@prism.test
        | Password : password
        |
        |--------------------------------------------------------------------------
        */
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@prism.test',
            'password' => bcrypt('password'),
            'division_id' => $divisions->first()->id,
        ]);

        $superAdmin->assignRole('super_admin');

        /*
        |--------------------------------------------------------------------------
        | ORGANIZATION ADMIN
        |--------------------------------------------------------------------------
        |
        | Email    : admin@prism.test
        | Password : password
        |
        |--------------------------------------------------------------------------
        */
        $organizationAdmin = User::factory()->create([
            'name' => 'Avery Morgan',
            'email' => 'admin@prism.test',
            'password' => bcrypt('password'),
            'division_id' => $divisions->first()->id,
        ]);

        $organizationAdmin->assignRole('admin');

        /*
        |--------------------------------------------------------------------------
        | DEMO USERS
        |--------------------------------------------------------------------------
        */
        $demoUsers = [
            [
                'name' => 'Afrizal',
                'email' => 'afrizal@prism.test',
                'role' => 'division_head',
                'division' => 'Engineering',
            ],
            [
                'name' => 'Kevin Anderson',
                'email' => 'kevin@prism.test',
                'role' => 'project_manager',
                'division' => 'Operations',
            ],
            [
                'name' => 'Sarah Williams',
                'email' => 'sarah@prism.test',
                'role' => 'project_manager',
                'division' => 'Product',
            ],
            [
                'name' => 'Daniel Carter',
                'email' => 'daniel@prism.test',
                'role' => 'project_manager',
                'division' => 'Finance',
            ],
            [
                'name' => 'Jessica Brown',
                'email' => 'jessica@prism.test',
                'role' => 'member',
                'division' => 'Customer Success',
            ],
            [
                'name' => 'Michael Scott',
                'email' => 'michael@prism.test',
                'role' => 'member',
                'division' => 'Operations',
            ],
            [
                'name' => 'Olivia Wilson',
                'email' => 'olivia@prism.test',
                'role' => 'member',
                'division' => 'Engineering',
            ],
            [
                'name' => 'James Miller',
                'email' => 'james@prism.test',
                'role' => 'member',
                'division' => 'Product',
            ],
            [
                'name' => 'Emma Davis',
                'email' => 'emma@prism.test',
                'role' => 'member',
                'division' => 'Finance',
            ],
            [
                'name' => 'William Taylor',
                'email' => 'william@prism.test',
                'role' => 'member',
                'division' => 'Customer Success',
            ],
            [
                'name' => 'Sophia Moore',
                'email' => 'sophia@prism.test',
                'role' => 'member',
                'division' => 'Engineering',
            ],
            [
                'name' => 'Lucas Martin',
                'email' => 'lucas@prism.test',
                'role' => 'member',
                'division' => 'Operations',
            ],
        ];

        $members = collect();

        foreach ($demoUsers as $data) {
            $division = $divisions->firstWhere(
                'name',
                $data['division']
            );

            $user = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
                'division_id' => $division->id,
            ]);

            $user->assignRole($data['role']);

            $members->push($user);
        }

        /*
        |--------------------------------------------------------------------------
        | PROJECT MANAGERS
        |--------------------------------------------------------------------------
        */
        $projectManagers = $members
            ->filter(
                fn (User $user): bool =>
                    $user->hasRole('project_manager')
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | ALL USERS
        |--------------------------------------------------------------------------
        */
        $allUsers = $members
            ->prepend($organizationAdmin)
            ->prepend($superAdmin);

        /*
        |--------------------------------------------------------------------------
        | DEMO PROJECTS
        |--------------------------------------------------------------------------
        */
        $projects = [
            [
                'name' => 'Personal Finance Tracker',
                'prefix' => 'FIN',
                'status' => 'In Progress',
            ],
            [
                'name' => 'Customer Portal Renewal',
                'prefix' => 'CPR',
                'status' => 'In Progress',
            ],
            [
                'name' => 'Operations Control Center',
                'prefix' => 'OPS',
                'status' => 'In Progress',
            ],
            [
                'name' => 'Mobile Service Experience',
                'prefix' => 'MOB',
                'status' => 'In Progress',
            ],
            [
                'name' => 'Quarterly Planning Cycle',
                'prefix' => 'QTR',
                'status' => 'Completed',
            ],
            [
                'name' => 'Knowledge Base Modernization',
                'prefix' => 'KB',
                'status' => 'Draft',
            ],
            [
                'name' => 'Finance Workflow Automation',
                'prefix' => 'FWA',
                'status' => 'In Progress',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | TASK TEMPLATES
        |--------------------------------------------------------------------------
        */
        $taskTemplates = [
            'Personal Finance Tracker' => [
                'Expense Categories Setup',
                'Add Expense Form',
                'Income Sources Setup',
                'Expense List & Filter',
                'Edit/Delete Expense',
                'Add Income Form',
                'Recurring Expenses',
                'Income List & History',
                'Recurring Income',
                'Budget Categories',
                'Monthly Budget Planning',
                'Budget vs Actual Tracking',
                'Budget Alert System',
                'Dashboard Financial Summary',
                'Export Financial Report',
            ],

            'Customer Portal Renewal' => [
                'Customer Requirements Discovery',
                'Portal Information Architecture',
                'Dashboard Redesign',
                'Account Profile Management',
                'Notification Center',
                'Document Management',
                'Customer Activity History',
                'Responsive Layout Implementation',
                'Accessibility Improvements',
                'Portal Integration Testing',
                'User Acceptance Testing',
                'Production Release',
            ],

            'Operations Control Center' => [
                'Operations Dashboard',
                'KPI Definition',
                'Real-time Monitoring',
                'Incident Tracking',
                'Operations Reports',
                'Alert Configuration',
                'Team Performance Metrics',
                'Data Aggregation Service',
                'Dashboard Filters',
                'Export Operations Report',
                'Quality Verification',
                'Production Deployment',
            ],

            'Mobile Service Experience' => [
                'Mobile Navigation',
                'Service Request Flow',
                'Customer Authentication',
                'Push Notification Setup',
                'Mobile Dashboard',
                'Service History',
                'Offline State Handling',
                'API Integration',
                'Mobile UI Testing',
                'Performance Optimization',
                'Beta Testing',
                'Release Preparation',
            ],

            'Quarterly Planning Cycle' => [
                'Planning Requirements',
                'Department Goal Collection',
                'Budget Allocation',
                'Quarterly Roadmap',
                'Milestone Definition',
                'Management Review',
                'Final Approval',
                'Quarterly Publication',
            ],

            'Knowledge Base Modernization' => [
                'Knowledge Audit',
                'Content Taxonomy',
                'Search Experience',
                'Article Templates',
                'Content Migration',
                'Authoring Workflow',
                'Knowledge Analytics',
                'Permissions Review',
                'Content QA',
                'Launch Preparation',
            ],

            'Finance Workflow Automation' => [
                'Approval Matrix',
                'Expense Approval Workflow',
                'Invoice Processing',
                'Budget Validation',
                'Finance Notifications',
                'Reconciliation Workflow',
                'Audit Trail',
                'Finance Dashboard',
                'Integration Testing',
                'UAT Finance Workflow',
                'Production Deployment',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | COMMENT TEMPLATES
        |--------------------------------------------------------------------------
        */
        $comments = [
            'Progress is on track for this milestone.',
            'I have added the latest review notes.',
            'Please confirm the remaining acceptance criteria.',
            'This is ready for the next project checkpoint.',
            'The implementation has been completed and is ready for review.',
            'I have updated the requirements based on stakeholder feedback.',
            'Testing results have been added to the ticket.',
            'The remaining issue has been assigned to the engineering team.',
            'Everything looks good from my side.',
            'The latest changes are ready for validation.',
            'I have completed the requested updates.',
            'This task is now ready for the next stage.',
        ];

        /*
        |--------------------------------------------------------------------------
        | CREATE PROJECTS
        |--------------------------------------------------------------------------
        */
        foreach ($projects as $projectIndex => $projectData) {
            /*
            |--------------------------------------------------------------------------
            | Project
            |--------------------------------------------------------------------------
            */
            $project = Project::factory()->create([
                'name' => $projectData['name'],
                'ticket_prefix' => $projectData['prefix'],
                'status' => $projectData['status'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Ticket Statuses
            |--------------------------------------------------------------------------
            */
            $statuses = collect([
                [
                    'name' => 'Backlog',
                    'color' => '#64748b',
                    'sort_order' => 0,
                    'is_completed' => false,
                ],
                [
                    'name' => 'To Do',
                    'color' => '#8b5cf6',
                    'sort_order' => 1,
                    'is_completed' => false,
                ],
                [
                    'name' => 'In Progress',
                    'color' => '#2563eb',
                    'sort_order' => 2,
                    'is_completed' => false,
                ],
                [
                    'name' => 'Testing',
                    'color' => '#f59e0b',
                    'sort_order' => 3,
                    'is_completed' => false,
                ],
                [
                    'name' => 'In Review',
                    'color' => '#d97706',
                    'sort_order' => 4,
                    'is_completed' => false,
                ],
                [
                    'name' => 'Done',
                    'color' => '#16a34a',
                    'sort_order' => 5,
                    'is_completed' => true,
                ],
            ])->map(
                fn (array $data) =>
                    $project->ticketStatuses()->create($data)
            );

            /*
            |--------------------------------------------------------------------------
            | Project Manager
            |--------------------------------------------------------------------------
            */
            $projectManager = $projectManagers->get(
                $projectIndex % $projectManagers->count()
            );

            /*
            |--------------------------------------------------------------------------
            | Project Team
            |--------------------------------------------------------------------------
            */
            $team = $members
                ->shuffle()
                ->take(random_int(5, 8))
                ->values();

            if (!$team->contains('id', $projectManager->id)) {
                $team->push($projectManager);
            }

            /*
            |--------------------------------------------------------------------------
            | Attach Project Members
            |--------------------------------------------------------------------------
            */
            $team
                ->unique('id')
                ->each(
                    function (User $user) use (
                        $project,
                        $projectManager
                    ): void {
                        $project->members()->syncWithoutDetaching([
                            $user->id => [
                                'role' => $user->id === $projectManager->id
                                    ? 'Project Manager'
                                    : 'Member',
                            ],
                        ]);
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Project Members
            |--------------------------------------------------------------------------
            */
            $projectMembers = $project->members()->get();

            /*
            |--------------------------------------------------------------------------
            | Base Date
            |--------------------------------------------------------------------------
            |
            | Data dibuat mundur/maju supaya Activity dan Timeline
            | terlihat seperti project sungguhan.
            |
            */
            $baseDate = Carbon::create(
                2025,
                2,
                18
            )->addDays($projectIndex * 3);

            /*
            |--------------------------------------------------------------------------
            | Create Tickets
            |--------------------------------------------------------------------------
            */
            foreach (
                $taskTemplates[$projectData['name']]
                as $taskIndex => $taskName
            ) {
                /*
                |--------------------------------------------------------------------------
                | Status Distribution
                |--------------------------------------------------------------------------
                |
                | Awal project:
                | Done / Review
                |
                | Tengah:
                | Testing / In Progress
                |
                | Akhir:
                | To Do
                |
                */
                $statusIndex = match (true) {
                    $taskIndex <= 1 => 5,
                    $taskIndex <= 3 => 4,
                    $taskIndex <= 6 => 3,
                    $taskIndex <= 9 => 2,
                    default => 1,
                };

                $status = $statuses->get(
                    min(
                        $statusIndex,
                        $statuses->count() - 1
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Ticket Dates
                |--------------------------------------------------------------------------
                */
                $startDate = $baseDate
                    ->copy()
                    ->addDays($taskIndex * 2);

                $duration = match (true) {
                    $taskIndex <= 2 => random_int(14, 20),
                    $taskIndex <= 6 => random_int(18, 26),
                    default => random_int(22, 35),
                };

                $endDate = $startDate
                    ->copy()
                    ->addDays($duration);

                /*
                |--------------------------------------------------------------------------
                | Assignees
                |--------------------------------------------------------------------------
                */
                $assignees = $projectMembers
                    ->shuffle()
                    ->take(
                        random_int(
                            1,
                            min(
                                3,
                                $projectMembers->count()
                            )
                        )
                    )
                    ->values();

                /*
                |--------------------------------------------------------------------------
                | Creator
                |--------------------------------------------------------------------------
                */
                $creator = $projectMembers->random();

                /*
                |--------------------------------------------------------------------------
                | Ticket
                |--------------------------------------------------------------------------
                */
                $task = Ticket::factory()->create([
                    'project_id' => $project->id,
                    'ticket_status_id' => $status->id,
                    'name' => $taskName,
                    'created_by' => $creator->id,

                    /*
                    |--------------------------------------------------------------------------
                    | Timestamp sengaja dibuat berbeda-beda
                    |--------------------------------------------------------------------------
                    | Ini membuat Timeline / Activity terlihat hidup.
                    */
                    'created_at' => $startDate,
                    'updated_at' => $status->is_completed
                        ? $endDate
                        : $startDate->copy()->addDays(
                            random_int(
                                1,
                                max(1, $duration - 1)
                            )
                        ),
                ]);

                /*
                |--------------------------------------------------------------------------
                | ASSIGNEES
                |--------------------------------------------------------------------------
                */
                $task->assignees()->sync(
                    $assignees->modelKeys()
                );

                /*
                |--------------------------------------------------------------------------
                | INITIAL HISTORY
                |--------------------------------------------------------------------------
                */
                TicketHistory::create([
                    'ticket_id' => $task->id,
                    'user_id' => $creator->id,
                    'ticket_status_id' => $status->id,
                    'created_at' => $startDate,
                    'updated_at' => $startDate,
                ]);

                /*
                |--------------------------------------------------------------------------
                | ADDITIONAL HISTORY
                |--------------------------------------------------------------------------
                */
                if ($status->sort_order >= 2) {
                    TicketHistory::create([
                        'ticket_id' => $task->id,
                        'user_id' => $creator->id,
                        'ticket_status_id' => $statuses->get(2)->id,
                        'created_at' => $startDate->copy()->addDays(2),
                        'updated_at' => $startDate->copy()->addDays(2),
                    ]);
                }

                if ($status->sort_order >= 3) {
                    TicketHistory::create([
                        'ticket_id' => $task->id,
                        'user_id' => $creator->id,
                        'ticket_status_id' => $statuses->get(3)->id,
                        'created_at' => $startDate->copy()->addDays(5),
                        'updated_at' => $startDate->copy()->addDays(5),
                    ]);
                }

                if ($status->sort_order >= 4) {
                    TicketHistory::create([
                        'ticket_id' => $task->id,
                        'user_id' => $creator->id,
                        'ticket_status_id' => $statuses->get(4)->id,
                        'created_at' => $startDate->copy()->addDays(8),
                        'updated_at' => $startDate->copy()->addDays(8),
                    ]);
                }

                if ($status->is_completed) {
                    TicketHistory::create([
                        'ticket_id' => $task->id,
                        'user_id' => $creator->id,
                        'ticket_status_id' => $status->id,
                        'created_at' => $endDate,
                        'updated_at' => $endDate,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | COMMENTS
                |--------------------------------------------------------------------------
                */
                $commenters = $assignees
                    ->shuffle()
                    ->take(
                        random_int(
                            1,
                            min(
                                3,
                                $assignees->count()
                            )
                        )
                    );

                foreach ($commenters as $commenter) {
                    $commentDate = $startDate
                        ->copy()
                        ->addDays(
                            random_int(
                                1,
                                max(1, $duration - 1)
                            )
                        );

                    TicketComment::create([
                        'ticket_id' => $task->id,
                        'user_id' => $commenter->id,
                        'comment' => fake()->randomElement(
                            $comments
                        ),
                        'created_at' => $commentDate,
                        'updated_at' => $commentDate,
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FINAL OUTPUT
        |--------------------------------------------------------------------------
        */
        $this->command?->newLine();

        $this->command?->info(
            '=============================================='
        );

        $this->command?->info(
            'PRISM DEMO DATA SEEDED SUCCESSFULLY'
        );

        $this->command?->info(
            '=============================================='
        );

        $this->command?->newLine();

        $this->command?->info(
            'Organization : ' . $organization->name
        );

        $this->command?->info(
            'Divisions    : ' . $divisions->count()
        );

        $this->command?->info(
            'Users        : ' . $allUsers->count()
        );

        $this->command?->info(
            'Projects     : ' . count($projects)
        );

        $this->command?->newLine();

        $this->command?->info(
            'DEMO ACCOUNTS'
        );

        $this->command?->info(
            'Super Admin  : superadmin@prism.test / password'
        );

        $this->command?->info(
            'Admin        : admin@prism.test / password'
        );

        $this->command?->info(
            'PM           : kevin@prism.test / password'
        );

        $this->command?->info(
            'PM           : sarah@prism.test / password'
        );

        $this->command?->info(
            'Member       : jessica@prism.test / password'
        );

        $this->command?->newLine();

        $this->command?->info(
            '=============================================='
        );
    }
}