from django.contrib import admin

from .models import Client, NetAddress, Space


class ClientAdmin(admin.ModelAdmin):
    list_display = ('name', 'domain', 'space')
    #pass

#admin.site.register(Client)
admin.site.register(Client, ClientAdmin)
admin.site.register(NetAddress)
admin.site.register(Space)

