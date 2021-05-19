'''
Created on 13 abr. 2021

@author: user
'''
from datastructures.sortedlinkedlist import SortedLinkedList


#from _collections import deque

class TimeFramesList:
    '''
    classdocs
    '''

    def __init__(self):
        '''
        Constructor
        '''
        self.__list = SortedLinkedList()
        
             
    def setList(self, newlist):
        self.__list = newlist 
         
        
    def getList(self):
        return self.__list        
    
    
    def size(self):
        return self.__list.size()    
     
    
    def addTimeFrame(self, timeframe):
        self.__list.insert(timeframe)
        
        
    def removeTimeFrame(self, timeframe):
        self.__list.remove(timeframe)  
        
        
    def isOverlapping(self):
        isOverlapping = False
        i = 0
        size = self.__list.size()
        while i < size - 1 and not isOverlapping:
            firstFrame = self.__list.getByIndex(i).getInfo()
            secondFrame = self.__list.getByIndex(i+1).getInfo()
            isOverlapping = firstFrame.isOverlapping( secondFrame )
            i += 1
            
        return isOverlapping  
    
    
    def joinOverlapping(self):
        
        while self.isOverlapping():
            joined = False
            i = 0
            size = self.__list.size() 
            while i < size - 1 and not joined:
                firstFrame = self.__list.getByIndex(i).getInfo()
                secondFrame = self.__list.getByIndex(i+1).getInfo()
                if firstFrame.isOverlapping( secondFrame ):
                    joinedFrame = firstFrame.union( secondFrame )
                    self.removeTimeFrame( firstFrame )
                    self.removeTimeFrame( secondFrame )
                    self.addTimeFrame( joinedFrame )
                    joined = True
                i += 1