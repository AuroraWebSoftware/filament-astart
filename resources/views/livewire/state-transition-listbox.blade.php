<div  class="relative inline-block text-left text-{{ $textSize }}" x-data="{open:false} " @click.outside="open = false">

    <button type="button" wire:loading.remove x-ref="btn" x-on:click="open = !open"
            class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-{{$bgColor}}-600 {{ $buttonPaddingClasses }}
                font-medium text-white shadow-sm ring-1 ring-inset ring-{{$bgColor}}-300 hover:bg-{{$bgColor}}-800"
            id="menu-button" aria-expanded="true" aria-haspopup="true">

        {{ $this->getLocalizedState($model->currentState()) }}

        <svg class="-mr-1 h-5 w-5 text-white-200 " viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                  clip-rule="evenodd"/>
        </svg>
    </button>

    <span wire:loading x-on:click="open = !open"
          class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-indigo-600 px-5 py-2
                font-medium text-white shadow-sm ring-1 ring-inset ring-{{$bgColor}}-400"
          id="menu-button-loading" aria-expanded="true" aria-haspopup="true">

           <svg aria-hidden="true" role="status" class="-mr-1 h-5 w-5 inline mr-3 ml-3 text-white animate-spin"
                viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                  fill="#E5E7EB"></path>
            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                  fill="currentColor"></path>
           </svg>

        </span>

    <div x-show="open" x-anchor.bottom-start="$refs.btn"
         class="absolute right-0 z-10 mt-2 w-64 origin-top-right divide-y divide-indigo-100 rounded-md text-sm
         bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
         role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1" x-cloak>
        @php
            $availableTransitions = collect($model->definedTransitionStates())
                ->filter(fn($state) => $model->canTransitionTo($state));
        @endphp
        @if($availableTransitions->isEmpty())
            <div class="rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1 md:flex md:justify-between">
                        {{ __('arflow.state-transition-listbox.no-defined-transition-state-found') }}
                    </div>
                </div>
            </div>
        @endif

        @foreach($model->definedTransitionStates() as $state)
            @if($model->canTransitionTo($state))
                <div x-cloak class="py-2" role="none">
                    <a href="#"
                       wire:click="transitionTo('{{$state}}')"
                       class="text-indigo-900 block px-4 py-2 text-{{ $textSize }}"
                       x-on:click="open = !open"
                       role="menuitem" tabindex="-1"
                       id="menu-item-{{$loop->index}}">

                        {{ $this->getLocalizedStateListbox($state) }}

                    </a>
                </div>
            @endif
        @endforeach

        @foreach($model->definedTransitionStates() as $state)
            @if(! $model->canTransitionTo($state) && ! $onlyAllowedTransitionStates)
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor"
                                 aria-hidden="true">
                                <path fill-rule="evenodd"
                                      d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-900">
                                {{ $this->getLocalizedStateListbox($state) }}
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>
                                    @foreach($model->transitionGuardResults($state)->messages() as $subMessages)
                                        @foreach($subMessages as $message)
                                           <li class="mt-1">{{ $message }}</li>
                                        @endforeach
                                    @endforeach
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
