# Create your views here.
from django.http import HttpResponse
from django.http import HttpResponseRedirect
from django.template import Context, loader

def home(request):
  t = loader.get_template('main/index.html')
  c = Context({})
  return HttpResponse(t.render(c))

def products(request):
  t = loader.get_template('main/products.html')
  c = Context({})
  return HttpResponse(t.render(c))
