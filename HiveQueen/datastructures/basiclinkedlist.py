'''
Created on 13 abr. 2021

@author: gregorio.corral@uc3m.es
'''
from datastructures import Node
#from django.contrib.admindocs.views import ViewIndexView
#from django.forms.utils import to_current_timezone
# import datastructures

class BasicLinkedList:
    '''
    classdocs
    '''

    def __init__(self):
        '''
        Constructor
        '''
        self.__first = None
    
    
    def setFirst(self, first):
        self.__first = first
    
    
    def getFirst(self):  
        return self.__first
    
    
    def getByIndex(self, index):
        if index < 0 or index > len(self):
            raise ValueError("Invalid index value: " + index)
        
        current = self.__first
        for i in range(index):
            current = current.getNext()
            
        return current        
        
      
    def isEmpty(self):
        return( self.__first == None )   
    
    
    def insert(self, info):
        node = Node(info)
        node.setNext( self.__first )
        self.__first = node
       
       
    def insertAt(self, info, index): 
        """
        """ 
        if index < 0 or index > len(self):
            raise ValueError("Invalid index value: " + index)
       
        newNode = Node(info)
        
        if self.__first == None or index == 0:
            newNode.setNext(self.__first)
            self.__first = newNode
        else:   
            previous = self.__first
            for i in range(index-1):
                previous = previous.getNext()
            
            newNode.setNext(previous.getNext())
            previous.setNext(newNode)
            
        
    def extract(self):
        info = None
        if not self.isEmpty():
            info = self.__first.getInfo()
            self.__first = self.__first.getNext()  
        return info    
            
            
    def insertAfter(self, info, previous):  
        if not isinstance(previous, Node):
            raise Exception("Invalid type argument: previous")
              
        newNode = Node(info)
        newNode.setNext( previous.getNext() )   
        previous.setNext( newNode ) 
     
     
    def extractAfter(self, previous):  
        if not isinstance(previous, Node):
            raise Exception("Invalid type argument: previous")     
        
        info = None   
        if previous.getNext() != None:
            info = previous.getNext().getInfo()
            previous.setNext( previous.getNext().getNext() ) 
        return info   
    
    
    def size(self):
        size = 0
        current = self.getFirst()
        
        while current != None:
            size += 1
            current = current.getNext()
        return size

     
    def __len__(self):
        return self.size()
             
            
    def __str__(self):        
        list_str = ""
        current = self.__first
        
        while current != None:
            list_str += current.getInfo()
            if current.getNext() != None:
                list_str += " -> "
            current = current.getNext()
            
        return list_str
    
    
    def searchNodeByInfo(self, info):
        target = None
        current = self.__first
        
        while current != None and not current.getInfo() == info:
            current = current.getNext()
            
        if current != None:
            target = current
            
        return target
    
    
    def __getitem__(self, index):
        return self.getByIndex(index)
    
     
    def getLast(self):
        current = self.__first
        
        while current != None and current.getNext() != None:
            current = current.getNext()
            
        return current
    
    
    def getIndexOf(self, info):
        current = self.__first
        index = -1
        
        if not self.isEmpty():
            index = 0
            while current != None and not current.getInfo() == info:
                index += 1
                current = current.getNext() 
        
        if current != None:
            return index
        else:
            return -1
        
    
    def occurrencesOf(self, info):
        current = self.__first
        counter = 0
        
        while current != None:
            if current.getInfo() == info:
                counter += 1
            current = current.getNext()
            
        return counter
        