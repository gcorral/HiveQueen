'''
Created on 7 abr. 2021

@author: Gregorio Corral Torres 
@contact: gregorio.corral@gmail.com
'''

import datetime
from sqlparse.sql import Begin

class TimeFrame:
    '''
    Time Frame 
    '''


    def __init__(self, begin, end):   
        #self.setBegin(begin)
        #self.setEnd(end)
        self.__timeBegin = begin
        self.__timeEnd = end
        
        if not isinstance(begin, datetime.datetime):
            raise Exception("Invalid type argument: begin")
        
        if not isinstance(end, datetime.datetime):
            raise Exception("Invalid type argument: end")
        
        if self.__timeBegin > self.__timeEnd:
            raise ValueError("Time Frame invalid: begin > end")  
    
    def getBegin(self):
        return self.__timeBegin
    
    def setBegin(self, begin):
        if not isinstance(begin, datetime.datetime):
            raise Exception("Invalid type argument: begin")
        
        if begin > self.getEnd():
            raise ValueError("Time Frame invalid: begin > end")
        
        self.__timeBegin = begin
     
    def getEnd(self):
        return self.__timeEnd
    
    def setEnd(self, end):
        if not isinstance(end, datetime.datetime):
            raise Exception("Invalid type argument: end")
        
        if end < self.__timeBegin:
            raise ValueError("Time Frame invalid: end < begin")
        
        self.__timeEnd = end
       
    def __eq__(self, other):
        if not isinstance(other, TimeFrame):
            raise Exception("Invalid type argument: other")
            
        if self.__timeBegin == other.getBegin() and self.__timeEnd == other.getEnd():
            return True
        else:
            return False   
    
    def isOverlapping(self,other):
        
        if not isinstance(other, TimeFrame):
            raise Exception("Invalid type argument: other")
       
        if self.getBegin() <= other.getBegin() and self.getEnd() >= other.getBegin():
            return True
        elif self.getBegin() > other.getBegin() and  self.getBegin() <= other.getEnd():
            return True
        else:
            return False   
        
        
    def union(self,other):  
        '''
        '''
        
        #if  not self.isOverlapping(other):
        #    raise ValueError("Time frames not overlapped !!!")
        
        if self.getBegin() <= other.getBegin():
            begin = self.getBegin()
        else:
            begin = other.getBegin()
            
        if  self.getEnd() >= other.getEnd():
            end = self.getEnd()
        else:
            end = other.getEnd()
            
        return TimeFrame(begin, end)
    
    
    def intersection(self,other): 
        
        if  not self.isOverlapping(other):
            raise ValueError("Time frames not overlapped !!!")
        
        if self.getBegin() <= other.getBegin():
            begin =  other.getBegin()
        else:
            begin = self.getBegin()
            
        if  self.getEnd() >= other.getEnd():
            end = other.getEnd()
        else:
            end = self.getEnd()  
            
        return TimeFrame(begin, end)                