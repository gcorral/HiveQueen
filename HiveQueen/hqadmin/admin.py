from django.contrib import admin
#from django.contrib.auth.models import User
from hqadmin.models import User
from django.contrib.auth.admin import UserAdmin
from hqadmin.forms import AddUserForm, UserChangeForm

# Unregister the provided model admin
#TODO.:
#admin.site.unregister(User)

# Register out own model admin, based on the default UserAdmin
@admin.register(User)
class HiveQueenUserAdmin(UserAdmin):
    #pass# Create your models here.from django.contrib import admin
    add_form = AddUserForm
    form = UserChangeForm
    model = User
    #list_display = ['email', 'username',]

admin.site.unregister(User)
admin.site.register(User, HiveQueenUserAdmin)

# Register your models here.
