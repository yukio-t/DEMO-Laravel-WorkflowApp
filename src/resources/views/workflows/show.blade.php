<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Workflow Detail
            </h2>
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('dashboard') }}">Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('transition'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ $errors->first('transition') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="text-sm text-gray-500 mb-2">Public ID</div>
                <div class="font-mono">{{ $workflow->public_id }}</div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Title</div>
                        <div class="font-semibold">{{ $workflow->title }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">State</div>
                        <div class="inline-block px-2 py-1 rounded bg-gray-100">{{ $workflow->current_state }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Creator</div>
                        <div>{{ $workflow->creator?->email }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Updated</div>
                        <div>{{ $workflow->updated_at?->format('Y-m-d H:i') }}</div>
                    </div>
                </div>

                @if ($workflow->body)
                    <div class="mt-4">
                        <div class="text-sm text-gray-500">Body</div>
                        <div class="whitespace-pre-wrap">{{ $workflow->body }}</div>
                    </div>
                @endif

                <div class="mt-6 flex gap-3">
                    @can('submit', $workflow)
                        <form method="POST" action="{{ route('workflows.submit', $workflow) }}">
                            @csrf
                            <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700" type="submit">
                                Submit
                            </button>
                        </form>
                    @endcan

                    @can('approve', $workflow)
                        <form method="POST" action="{{ route('workflows.approve', $workflow) }}">
                            @csrf
                            <button class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700" type="submit">
                                Approve
                            </button>
                        </form>
                    @endcan
                </div>

                    @can('reject', $workflow)
                        <form method="POST" action="{{ route('workflows.reject', $workflow) }}">
                            @csrf
                            <div class="mt-4">
                                <input class="border rounded px-2 py-2 w-full" type="text" name="comment" placeholder="Reject comment (optional)" />
                            </div>
                            <div class="mt-4">
                                <button class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700" type="submit">
                                    Reject
                                </button>
                            </div>
                        </form>
                    @endcan
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">History</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-4">At</th>
                                <th class="py-2 pr-4">Actor</th>
                                <th class="py-2 pr-4">From</th>
                                <th class="py-2 pr-4">To</th>
                                <th class="py-2 pr-4">Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workflow->histories->sortByDesc('created_at') as $h)
                                <tr class="border-b">
                                    <td class="py-2 pr-4">{{ optional($h->created_at)->format('Y-m-d H:i:s') }}</td>
                                    <td class="py-2 pr-4">{{ $h->actor?->email }}</td>
                                    <td class="py-2 pr-4">{{ $h->from_state ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $h->to_state }}</td>
                                    <td class="py-2 pr-4">{{ $h->comment }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-xs text-gray-500">
                    Demo behavior: transitions are executed via WorkflowService and recorded in workflow_histories.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
