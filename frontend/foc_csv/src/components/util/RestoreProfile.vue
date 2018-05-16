<template>
<div class="form-group">
  <label class="label label-default">
    <slot></slot>
  </label>
  <input ref="restore_profile_name" :placeholder="$t('Profile name')" type="text" class="form-control">
  <textarea ref="restore_profile_data" class="form-control"></textarea>

  <button class="btn btn-primary" @click.prevent="restoreToProfile"><i class="fa fa-floppy-o"></i> {{ $t('Restore') }}</button>
</div>
</template>

<script>
export default {
  methods: {
    restoreToProfile () {
      let name = this.$refs.restore_profile_name.value
      let profile = JSON.parse(this.$refs.restore_profile_data.value)
      this.$emit('restore', { name, profile })
    }
  }
}
</script>
