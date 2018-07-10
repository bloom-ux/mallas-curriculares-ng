<div id="curriculum-groups">
	<div v-if="groups">
		<div v-for="group in groups">
			<div class="curriculum-group">
				<div class="curriculum-group__title">
					{{ group.name }}
				</div>
				<div class="curriculum-group__color">
					{{ group.color }}
				</div>
			</div>
		</div>
	</div>
	<div class="curriculum-group__new" v-if="isCreating">
		<h3>Crear nuevo grupo</h3>
		<div class="curriculum-group__new-title curriculum-group__new-field">
			<label for="">Nombre del grupo</label>
			<input type="text" name="" id="" class="widefat" v-model="new_group_name">
		</div>
		<div class="curriculum-group__new-field">
			<label for=""></label>
			<input type="text" class="small-text" v-model="new_group_color">
		</div>
		<button type="button" class="button module__create" v-on:click="add">Guardar</button>
	</div>
	<div v-else>
		<button type="button" class="button module__create" v-on:click="create">Agregar nuevo grupo</button>
	</div>
</div>