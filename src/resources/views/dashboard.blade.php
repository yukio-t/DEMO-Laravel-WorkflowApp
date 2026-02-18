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
                                        <td class="py-2 pr-4 space-x-2">
                                            <a class="text-indigo-600 hover:underline" href="{{ route('workflows.show', $wf) }}">
                                                View
                                            </a>

                                            @can('submit', $wf)
                                                <form class="inline" method="POST" action="{{ route('workflows.submit', $wf) }}">
                                                    @csrf
                                                    <button class="text-blue-600 hover:underline" type="submit">Submit</button>
                                                </form>
                                            @endcan

                                            @can('approve', $wf)
                                                <form class="inline" method="POST" action="{{ route('workflows.approve', $wf) }}">
                                                    @csrf
                                                    <button class="text-green-600 hover:underline" type="submit">Approve</button>
                                                </form>
                                            @endcan

                                            @can('reject', $wf)
                                                <form class="inline" method="POST" action="{{ route('workflows.reject', $wf) }}">
                                                    @csrf
                                                    <input type="hidden" name="comment" value="Rejected by demo action" />
                                                    <button class="text-red-600 hover:underline" type="submit">Reject</button>
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
                        Demo behavior: filtering is currently controller-based (not Policy yet).
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
