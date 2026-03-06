<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
            <div class="text-sm text-gray-600">
                Role: <span class="font-semibold">{{ $role }}</span>
            </div>
        </div>
    </x-slot>

    @can('create', \App\Models\Workflow::class)
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <a class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700"
                        href="{{ route('workflows.create') }}">
                        New Workflow
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Workflows</h3>

                        @if ($role === 'applicant')
                            <span class="text-sm text-gray-600">
                                You see only your workflows.
                            </span>
                        @elseif ($role === 'approver')
                            <span class="text-sm text-gray-600">
                                You see submitted workflows.
                            </span>
                        @else
                            <span class="text-sm text-gray-600">
                                You see all workflows.
                            </span>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-4">ID</th>
                                    <th class="py-2 pr-4">Title</th>
                                    <th class="py-2 pr-4">State</th>
                                    <th class="py-2 pr-4">Creator</th>
                                    <th class="py-2 pr-4">Updated</th>
                                    <th class="py-2 pr-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($workflows as $wf)
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">{{ $wf->public_id }}</td>
                                        <td class="py-2 pr-4">{{ $wf->title }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="px-2 py-1 rounded bg-gray-100">
                                                {{ $wf->current_state }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4">{{ $wf->creator?->email }}</td>
                                        <td class="py-2 pr-4">{{ $wf->updated_at?->format('Y-m-d H:i') }}</td>
                                        <td class="py-2 ">
                                            <a class="inline-block text-white bg-indigo-600 hover:bg-indigo-400 px-4 py-2 rounded" href="{{ route('workflows.show', $wf) }}">
                                                View
                                            </a>

                                            @can('submit', $wf)
                                                <form class="inline" method="POST" action="{{ route('workflows.submit', $wf) }}">
                                                    @csrf
                                                    <button class="text-white bg-blue-600 hover:bg-blue-400 px-4 py-2 rounded" type="submit">Submit</button>
                                                </form>
                                            @endcan

                                            @can('approve', $wf)
                                                <form class="inline" method="POST" action="{{ route('workflows.approve', $wf) }}">
                                                    @csrf
                                                    <button class="text-white bg-green-600 hover:bg-green-400 px-4 py-2 rounded" type="submit">Approve</button>
                                                </form>
                                            @endcan

                                            @can('reject', $wf)
                                                <form class="inline" method="POST" action="{{ route('workflows.reject', $wf) }}">
                                                    @csrf
                                                    <input type="hidden" name="comment" value="Rejected by demo action" />
                                                    <button class="text-white bg-red-600 hover:bg-red-400 px-4 py-2 rounded" type="submit">Reject</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-6 text-gray-500" colspan="5">
                                            No workflows found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        Demo .
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
