from django.contrib import admin
from django.contrib.auth.models import User
from django.contrib.auth.admin import UserAdmin

# Unregister the provided model admin
#TODO.:
admin.site.unregister(User)

# Register out own model admin, based on the default UserAdmin
@admin.register(User)
class HiveQueenUserAdmin(UserAdmin):
    pass# Create your models here.from django.contrib import admin

# Register your models here.
