<div x-data="{
        show: false,
        message: '',
        type: '',
        duration: 3000,
        showAlert(message, type = 'success', duration = 3000) {
            this.message = message;
            this.type = type; // 'success' atau 'error'
            this.duration = duration;
            this.show = true;
            <!-- setTimeout(() => this.show = false, this.duration); -->
        }
    }"
    x-on:success.window="showAlert($event.detail[0].message, 'success', 3000)"
    x-on:error.window="showAlert($event.detail[0].message, 'error', 3000)"
    x-cloak>

    <div x-show="show" :class="{
            'alert': true,
            'alert-primary': type === 'success',
            'alert-danger': type === 'error'
        }">
        <span x-text="message"></span>
    </div>
</div>
