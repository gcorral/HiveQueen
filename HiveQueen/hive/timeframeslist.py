'''
Created on 13 abr. 2021

@author: user
'''


from _collections import deque

class TimeFramesList:
    '''
    classdocs
    '''

    def __init__(self, params):
        '''
        Constructor
        '''
        self.__list = deque()
        
             
    def setList(self, newlist):
        self.__list = newlist 
         
        
    def getList(self):
        return self.__list        
        
     
    def addTimeFrame(self, timeframe):
        self.__list.append(timeframe)   